<?php

namespace App\Livewire\Portal;

use App\Models\Invoice;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.portal')]
class Invoices extends Component
{
    use WithPagination;

    #[Url]
    public string $search = '';

    #[Url]
    public string $sort = 'created_at';

    #[Url]
    public string $direction = 'desc';

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function sortBy(string $column): void
    {
        if (! in_array($column, ['number', 'status', 'due_date', 'total', 'created_at'], true)) {
            return;
        }

        if ($this->sort === $column) {
            $this->direction = $this->direction === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sort = $column;
            $this->direction = 'asc';
        }
    }

    public function render()
    {
        $customer = auth()->user()->customer;

        $invoices = $customer
            ? $customer->invoices()
                ->with('subscription')
                ->when($this->search, fn ($query) => $query->where('number', 'like', '%'.$this->search.'%'))
                ->orderBy($this->sort, $this->direction)
                ->paginate(10)
            : Invoice::query()->whereRaw('1 = 0')->paginate(10);

        return view('livewire.portal.invoices', compact('invoices'));
    }
}
