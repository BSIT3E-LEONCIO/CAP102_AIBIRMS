<?php

namespace App\Livewire;

use App\Services\FirebaseUserService;
use Livewire\Component;
use App\Models\User;
use Illuminate\Support\Facades\Mail;

use App\Mail\WelcomeUserMail;
use Illuminate\Support\Facades\DB;

class UserManagement extends Component
{
    public $addUserModal = false;
    public $editMode = false;
    public $userId;
    public $name, $username, $email, $password, $mobile, $role, $responder_type, $assigned_area;

    // Search and filter properties
    public $search = '';
    public $roleFilter = 'all';

    protected function rules()
    {
        $rules = [
            'name' => 'required|string|max:255',
            'username' => 'required|string|max:50|alpha_dash|unique:users,username',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:6',
            'mobile' => 'required|string|max:20',
            'role' => 'required|in:admin,responder,cctv',
            'assigned_area' => 'required|string|max:255',
        ];
        if ($this->role === 'responder') {
            $rules['responder_type'] = 'required|in:police,fire,medical,tanod';
        }
        return $rules;
    }


    public function openModal()
    {
        $this->reset(['name', 'username', 'email', 'mobile', 'role', 'responder_type', 'assigned_area', 'userId']);
        $this->password = $this->generatePassword();
        $this->editMode = false;
        $this->addUserModal = true;
    }

    public function clearFilters()
    {
        $this->search = '';
        $this->roleFilter = 'all';
    }

    public function editUser($id)
    {
        $user = User::findOrFail($id);
        $this->userId = $user->id;
        $this->name = $user->name;
        $this->username = $user->username;
        $this->email = $user->email;
        $this->mobile = $user->mobile;
        $this->role = $user->role;
        $this->responder_type = $user->responder_type;
        $this->assigned_area = $user->assigned_area;
        $this->password = '********'; // Not editable
        $this->editMode = true;
        $this->addUserModal = true;
    }

    private function generatePassword($length = 10)
    {
        // Use Laravel's cryptographically secure password generator
        return \Illuminate\Support\Str::password($length);
    }

    public function closeModal()
    {
        $this->addUserModal = false;
        $this->editMode = false;
        $this->reset(['name', 'username', 'email', 'password', 'mobile', 'role', 'responder_type', 'assigned_area', 'userId']);
    }

    public function saveUser()
    {
        $this->validate($this->rules());
        $user = User::create([
            'name' => $this->name,
            'username' => $this->username,
            'email' => $this->email,
            'password' => bcrypt($this->password),
            'mobile' => $this->mobile,
            'role' => $this->role,
            'responder_type' => $this->role === 'responder' ? $this->responder_type : null,
            'assigned_area' => $this->assigned_area,
            'status' => 'active',
        ]);

        // Sync to Firebase Auth and Realtime Database only if role is 'cctv'
        if ($this->role === 'cctv') {
            try {
                $firebaseService = new FirebaseUserService();
                $firebaseService->createUser(
                    $this->email,
                    $this->password,
                    $this->name,
                    $this->role,
                    [
                        'mobile' => $this->mobile,
                        'assigned_area' => $this->assigned_area,
                        'status' => 'active',
                    ]
                );
            } catch (\Exception $e) {
                // Log or handle Firebase error
            }
        }

        // Send welcome email with credentials
        try {
            Mail::to($user->email)->send(new WelcomeUserMail($user, $this->password));
        } catch (\Exception $e) {
            // Log or handle mail error
        }

        // Send email verification only for admin and responder roles
        if (in_array($user->role, ['admin', 'responder'])) {
            try {
                $user->sendEmailVerificationNotification();
            } catch (\Exception $e) {
                // Log or handle verification email error
            }
        }

        $this->closeModal();
        $this->reset(['name', 'email', 'password', 'mobile', 'role', 'assigned_area']);
        $this->dispatch('user-added');
    }

    public function updateUser()
    {
        $user = User::findOrFail($this->userId);
        $rules = [
            'name' => 'required|string|max:255',
            'username' => 'required|string|max:50|alpha_dash|unique:users,username,' . $user->id,
            'email' => 'required|email|unique:users,email,' . $user->id,
            'mobile' => 'required|string|max:20',
            'role' => 'required|in:admin,responder,cctv',
            'assigned_area' => 'required|string|max:255',
        ];
        if ($this->role === 'responder') {
            $rules['responder_type'] = 'required|in:police,fire,medical,tanod';
        }
        $this->validate($rules);
        $user->update([
            'name' => $this->name,
            'username' => $this->username,
            'email' => $this->email,
            'mobile' => $this->mobile,
            'role' => $this->role,
            'responder_type' => $this->role === 'responder' ? $this->responder_type : null,
            'assigned_area' => $this->assigned_area,
        ]);

        // Send email notification about updated data (no password)
        try {
            Mail::to($user->email)->send(new \App\Mail\UserUpdatedMail($user));
        } catch (\Exception $e) {
            // Log or handle mail error
        }

        $this->closeModal();
        $this->dispatch('user-updated');
    }

    public function toggleStatus($id)
    {
        $user = User::findOrFail($id);
        $user->status = $user->status === 'active' ? 'inactive' : 'active';
        $user->save();
        // Force logout if deactivated
        if ($user->status === 'inactive') {
            DB::table('sessions')->where('user_id', $user->id)->delete();
        }
        $this->dispatch('user-status-toggled');
    }

    public function confirmDeleteUser($id)
    {
        $this->dispatch('confirm-delete', $id);
    }

    public function deleteUser($id)
    {
        logger('Deleting user: ' . $id);
        $user = User::findOrFail($id);
        // If user is CCTV, delete from Firebase
        if ($user->role === 'cctv') {
            $firebaseService = new FirebaseUserService();
            $firebaseService->deleteUserByEmail($user->email);
        }
        $user->delete();
        $this->dispatch('user-deleted');
    }

    public function render()
    {
        $query = User::query();

        // Apply search filter
        if (!empty($this->search)) {
            $query->where(function ($q) {
                $q->where('name', 'LIKE', '%' . $this->search . '%')
                    ->orWhere('email', 'LIKE', '%' . $this->search . '%')
                    ->orWhere('username', 'LIKE', '%' . $this->search . '%')
                    ->orWhere('mobile', 'LIKE', '%' . $this->search . '%')
                    ->orWhere('assigned_area', 'LIKE', '%' . $this->search . '%');
            });
        }

        // Apply role filter
        if ($this->roleFilter !== 'all') {
            $query->where('role', $this->roleFilter);
        }

        // Order by created date (oldest first) - admin will appear first
        $query->orderBy('created_at', 'asc');

        return view('livewire.user-management', [
            'users' => $query->get()
        ]);
    }
}
