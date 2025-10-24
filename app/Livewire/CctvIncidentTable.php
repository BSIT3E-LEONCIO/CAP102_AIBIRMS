<?php

namespace App\Livewire;

use App\Models\Incident;
use Livewire\Component;
use Livewire\WithPagination;
use Carbon\Carbon;

class CctvIncidentTable extends Component
{
    use WithPagination;

    public $search = '';

    public $perPage = 10;

    public $typeFilter = '';

    public $statusFilter = '';

    public $sortField = 'timestamp';

    public $sortDirection = 'desc';

    public $selectedIncidents = [];

    public $selectAll = false;

    public $showHidden = false;

    // Date filtering controls (shared with report generator)
    public $period = 'day'; // day|week|month|year
    public $anchorDate; // Y-m-d (used for day/week/month)
    public $allYears = false; // legacy compatibility (report param)
    public $yearSelection = 'all'; // 'all' or 4-digit year string
    public $years = [];

    protected $updatesQueryString = ['search', 'typeFilter', 'statusFilter', 'sortField', 'sortDirection', 'page', 'perPage', 'showHidden', 'period', 'anchorDate', 'yearSelection'];

    public function mount()
    {
        $this->anchorDate = $this->anchorDate ?: now()->toDateString();
        // migrate legacy query param allYears to yearSelection if present
        if ($this->period === 'year') {
            if ($this->allYears) {
                $this->yearSelection = 'all';
            } elseif ($this->anchorDate) {
                $this->yearSelection = (string) Carbon::parse($this->anchorDate)->year;
            }
        }
    }

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatingTypeFilter()
    {
        $this->resetPage();
    }

    public function updatingPerPage()
    {
        $this->resetPage();
    }

    public function updatingPeriod()
    {
        $this->resetPage();
    }

    public function updatingAnchorDate()
    {
        $this->resetPage();
    }

    public function updatingAllYears()
    {
        $this->resetPage();
    }

    public function updatingYearSelection()
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

    public function updatedSelectAll($value)
    {
        if ($value) {
            // Only select IDs of incidents currently displayed (after filters, sorting, and pagination)
            $incidents = $this->getCurrentIncidents();
            // Ensure we pluck from the paginator items collection
            $this->selectedIncidents = collect($incidents->items())->pluck('id')->toArray();
        } else {
            $this->selectedIncidents = [];
        }
    }

    /**
     * Get the current paginated incidents as displayed in the table (after filters, sorting, and pagination)
     */
    protected function getCurrentIncidents()
    {
        $query = Incident::query()->where('source', 'cctv');
        $query->where('hidden', $this->showHidden);
        $this->applyDateFilter($query);
        if ($this->search) {
            $s = '%' . $this->search . '%';
            $query->where(function ($q) use ($s) {
                $q->where('firebase_id', 'like', $s)
                    ->orWhere('type', 'like', $s)
                    ->orWhere('location', 'like', $s)
                    ->orWhere('department', 'like', $s)
                    ->orWhere('status', 'like', $s)
                    ->orWhere('timestamp', 'like', $s)
                    ->orWhereRaw("DATE_FORMAT(timestamp, '%M') LIKE ?", [$s])
                    ->orWhereRaw("DATE_FORMAT(timestamp, '%Y') LIKE ?", [$s])
                    ->orWhereRaw("DATE_FORMAT(timestamp, '%d') LIKE ?", [$s])
                    ->orWhereRaw("DATE_FORMAT(timestamp, '%M %d, %Y') LIKE ?", [$s]);
            });
        }
        if ($this->typeFilter) {
            $query->where('type', $this->typeFilter);
        }
        if ($this->statusFilter) {
            $query->where('status', $this->statusFilter);
        }

        return $query->orderBy($this->sortField, $this->sortDirection)->paginate($this->perPage);
    }

    protected function applyDateFilter($query)
    {
        $date = $this->anchorDate ? Carbon::parse($this->anchorDate) : Carbon::now();
        switch ($this->period) {
            case 'day':
                $query->whereDate('timestamp', $date->toDateString());
                break;
            case 'week':
                $query->whereBetween('timestamp', [$date->copy()->startOfWeek(), $date->copy()->endOfWeek()]);
                break;
            case 'month':
                $query->whereYear('timestamp', $date->year)->whereMonth('timestamp', $date->month);
                break;
            case 'year':
                // If a specific year is chosen, filter by it; otherwise no year filter
                if ($this->yearSelection !== 'all') {
                    $query->whereYear('timestamp', (int) $this->yearSelection);
                }
                break;
        }
    }

    public function updatedSelectedIncidents()
    {
        $this->selectAll = false;
    }

    public function hideSelected()
    {
        if (! empty($this->selectedIncidents)) {
            Incident::whereIn('id', $this->selectedIncidents)->update(['hidden' => true]);
            $this->selectedIncidents = [];
            $this->selectAll = false;
            session()->flash('status', 'Selected incidents have been hidden.');
        }
    }

    public function unhideSelected()
    {
        if (! empty($this->selectedIncidents)) {
            Incident::whereIn('id', $this->selectedIncidents)->update(['hidden' => false]);
            $this->selectedIncidents = [];
            $this->selectAll = false;
            session()->flash('status', 'Selected incidents have been unhidden.');
        }
    }

    public function toggleShowHidden()
    {
        $this->showHidden = ! $this->showHidden;
        $this->selectedIncidents = [];
        $this->selectAll = false;
    }

    public function render()
    {
        $query = Incident::query()->where('source', 'cctv');
        $query->where('hidden', $this->showHidden);
        $this->applyDateFilter($query);
        if ($this->search) {
            $s = '%' . $this->search . '%';
            $query->where(function ($q) use ($s) {
                $q->where('firebase_id', 'like', $s)
                    ->orWhere('type', 'like', $s)
                    ->orWhere('location', 'like', $s)
                    ->orWhere('department', 'like', $s)
                    ->orWhere('status', 'like', $s)
                    ->orWhere('timestamp', 'like', $s)
                    ->orWhereRaw("DATE_FORMAT(timestamp, '%M') LIKE ?", [$s])
                    ->orWhereRaw("DATE_FORMAT(timestamp, '%Y') LIKE ?", [$s])
                    ->orWhereRaw("DATE_FORMAT(timestamp, '%d') LIKE ?", [$s])
                    ->orWhereRaw("DATE_FORMAT(timestamp, '%M %d, %Y') LIKE ?", [$s]);
            });
        }
        if ($this->typeFilter) {
            $query->where('type', $this->typeFilter);
        }
        if ($this->statusFilter) {
            $query->where('status', $this->statusFilter);
        }
        $incidents = $query->orderBy($this->sortField, $this->sortDirection)->paginate($this->perPage);
        $types = Incident::query()->where('source', 'cctv')->distinct()->pluck('type');
        $this->years = Incident::query()
            ->where('source', 'cctv')
            ->selectRaw('YEAR(timestamp) as y')
            ->distinct()
            ->orderBy('y', 'desc')
            ->pluck('y')
            ->toArray();

        return view('livewire.cctv-incident-table', [
            'incidents' => $incidents,
            'types' => $types,
            'years' => $this->years,
        ]);
    }
}
