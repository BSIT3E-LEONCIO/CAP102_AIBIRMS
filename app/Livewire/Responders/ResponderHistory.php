<?php

namespace App\Livewire\Responders;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Incident;
use App\Models\IncidentLog;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ResponderHistory extends Component
{
    use WithPagination;

    public $search = '';
    public $perPage = 10;
    public $dateFilter = 'all';
    public $sortField = 'timestamp';
    public $sortDirection = 'desc';

    protected $updatesQueryString = ['search', 'sortField', 'sortDirection', 'dateFilter', 'page'];
    public function updatingDateFilter()
    {
        $this->resetPage();
    }

    public function updatingSearch()
    {
        $this->resetPage();
    }
    public function updatingPerPage()
    {
        $this->resetPage();
    }

    public function sortBy($field)
    {
        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortField = $field;
            $this->sortDirection = 'asc';
        }
    }

    public function render()
    {
        $user = Auth::user();
        $query = Incident::query();
        // Only show resolved incidents assigned to this responder (via dispatches)
        if ($user && isset($user->id)) {
            $query->where('status', 'resolved')
                ->whereIn('firebase_id', function ($sub) use ($user) {
                    $sub->select('incident_id')
                        ->from('dispatches')
                        ->where('responder_id', $user->id);
                })
                // Attach the responder's dispatch id for this incident to build view link
                ->addSelect([
                    'dispatch_id' => DB::table('dispatches')
                        ->select('id')
                        ->whereColumn('dispatches.incident_id', 'incidents.firebase_id')
                        ->where('responder_id', $user->id)
                        ->limit(1)
                ]);
        } else {
            $query->whereRaw('0=1');
        }
        if ($this->search) {
            $s = '%' . $this->search . '%';
            $query->where(function ($q) use ($s) {
                $q->where('firebase_id', 'like', $s)
                    ->orWhere('type', 'like', $s)
                    ->orWhere('location', 'like', $s)
                    ->orWhere('status', 'like', $s)
                    ->orWhere('incident_description', 'like', $s)
                    ->orWhere('timestamp', 'like', $s)
                    ->orWhereRaw("DATE_FORMAT(timestamp, '%M') LIKE ?", [$s])
                    ->orWhereRaw("DATE_FORMAT(timestamp, '%Y') LIKE ?", [$s])
                    ->orWhereRaw("DATE_FORMAT(timestamp, '%d') LIKE ?", [$s])
                    ->orWhereRaw("DATE_FORMAT(timestamp, '%M %d, %Y') LIKE ?", [$s]);
            });
        }
        // Date filter logic
        if ($this->dateFilter === 'today') {
            $query->whereDate('timestamp', now()->toDateString());
        } elseif ($this->dateFilter === 'last24') {
            $query->where('timestamp', '>=', now()->subDay());
        } elseif ($this->dateFilter === 'last7') {
            $query->where('timestamp', '>=', now()->subDays(7));
        } elseif ($this->dateFilter === 'last30') {
            $query->where('timestamp', '>=', now()->subDays(30));
        }
        $incidents = $query->orderBy($this->sortField, $this->sortDirection)->paginate($this->perPage);
        return view('livewire.responder-history', [
            'incidents' => $incidents,
        ]);
    }
}
