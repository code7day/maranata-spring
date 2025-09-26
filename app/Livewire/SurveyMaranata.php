<?php

namespace App\Livewire;

use App\Enums\TransportEnum;
use App\Models\Participation;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\Attributes\Computed;
use Illuminate\Validation\Rules\Enum as EnumRule;
use Carbon\Carbon;
use Illuminate\Support\Facades\Session;
use Livewire\WithPagination;

class SurveyMaranata extends Component
{
    use WithPagination;

    public $fullName = '';
    public $cellphone = '';
    public ?TransportEnum $transport = null;
    public $seats = 1;

    public bool $showSeatsInput = false;

    public int $busCapacity = 45;
    public int $maxSeatsPerReservation = 10;

    public string $reportPassword = '';
    public ?string $passwordError = null;

    public string $deadlineIsoString;
    public bool $isReportUnlocked = false;

    // Propiedades para el reporte
    public string $filterBy = 'all';
    public string $sortBy = 'created_at';
    public string $sortDirection = 'desc';

    public function mount()
    {
        $this->deadlineIsoString = Carbon::today('America/Lima')->setTime(14, 0, 0)->toIso8601String();

        if (Session::get('report_unlocked')) {
            $this->isReportUnlocked = true;
        }
    }

    // --- Funciones para el Reporte ---
    public function filter($type)
    {
        $this->filterBy = $type;
        $this->resetPage();
    }

    public function sortBy($field)
    {
        if ($this->sortBy === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortDirection = 'asc';
        }
        $this->sortBy = $field;
    }
    // --- Fin de Funciones ---

    public function updatedTransport(?TransportEnum $value)
    {
        $this->showSeatsInput = ($value === TransportEnum::BUS);
    }

    public function resetForm()
    {
        $this->reset(['fullName', 'cellphone', 'transport', 'seats', 'showSeatsInput', 'reportPassword', 'passwordError']);
        $this->resetErrorBag();
    }

    #[Computed]
    public function maxSeatsAllowed()
    {
        return min($this->maxSeatsPerReservation, $this->availableBusSeats());
    }

    protected function rules()
    {
        return [
            'fullName' => 'required|string|min:3|max:255',
            'cellphone' => 'required|string|digits:9',
            'transport' => ['required', new EnumRule(TransportEnum::class)],
            'seats' => [
                'required_if:showSeatsInput,true',
                'integer',
                'min:1',
                'max:' . $this->maxSeatsAllowed,
            ],
        ];
    }

    protected $messages = [
        'fullName.required' => 'Tu nombre completo es necesario.',
        'cellphone.required' => 'Tu número de celular es necesario.',
        'cellphone.digits' => 'El celular debe tener 9 dígitos.',
        'transport.required' => 'Por favor, elige una opción de transporte.',
        'seats.required_if' => 'Indica el número de asientos por favor.',
        'seats.min' => 'Debes reservar al menos 1 asiento.',
        'seats.max' => 'Solo puedes reservar un máximo de :max asientos disponibles.',
    ];

    public function checkPassword()
    {
        if ($this->reportPassword === 'mrnt$2025') {
            $this->passwordError = null;
            $this->reportPassword = '';
            Session::put('report_unlocked', true);
            $this->isReportUnlocked = true;
            $this->dispatch('report-unlocked');
        } else {
            $this->passwordError = 'La clave ingresada es incorrecta.';
        }
    }

    public function save()
    {
        $this->validate();

        $seatsToSave = 0;
        if ($this->transport instanceof TransportEnum && $this->transport === TransportEnum::BUS) {
            $seatsToSave = (int) $this->seats;
        }

        Participation::create([
            'full_name' => $this->fullName,
            'cellphone' => $this->cellphone,
            'transport' => $this->transport,
            'seats' => $seatsToSave,
        ]);

        $this->dispatch('participation-saved', availableSeats: $this->availableBusSeats());
        $this->resetForm();
    }

    // --- INDICADORES GLOBALES (NO CAMBIAN CON FILTROS) ---
    #[Computed]
    public function allParticipations()
    {
        return Participation::all();
    }

    #[Computed]
    public function totalParticipants()
    {
        return $this->allParticipations->sum(function ($p) {
            return $p->transport === TransportEnum::INDIVIDUAL ? 1 : (int) $p->seats;
        });
    }

    #[Computed]
    public function totalBusParticipants()
    {
        return $this->allParticipations->where('transport', TransportEnum::BUS)->sum('seats');
    }

    #[Computed]
    public function totalOwnParticipants()
    {
        return $this->allParticipations->where('transport', TransportEnum::INDIVIDUAL)->count();
    }

    #[Computed]
    public function totalRegistrations()
    {
        return $this->allParticipations->count();
    }

    #[Computed]
    public function busSeats()
    {
        return $this->totalBusParticipants;
    }

    #[Computed]
    public function availableBusSeats()
    {
        return max(0, $this->busCapacity - $this->busSeats);
    }

    #[Computed]
    public function isBusBookingOver()
    {
        return now()->gt(Carbon::parse($this->deadlineIsoString));
    }

    #[Computed]
    public function isBusDisabled()
    {
        return $this->availableBusSeats <= 0 || $this->isBusBookingOver;
    }

    #[Computed]
    public function busesNeeded()
    {
        if ($this->busSeats <= 0) { return 0; }
        return ceil($this->busSeats / $this->busCapacity);
    }

    #[Computed]
    public function busIncome()
    {
        $passagePrice = 10;
        return $this->busSeats * $passagePrice;
    }
    // --- FIN DE INDICADORES GLOBALES ---

    #[Layout('components.layouts.web')]
    public function render()
    {
        $query = Participation::query();

        if ($this->filterBy === 'bus') {
            $query->where('transport', TransportEnum::BUS);
        } elseif ($this->filterBy === 'individual') {
            $query->where('transport', TransportEnum::INDIVIDUAL);
        }

        $query->orderBy($this->sortBy, $this->sortDirection);

        $participations = $query->get();

        // Se calculan los datos para el gráfico basados en la lista filtrada
        $busChartCount = $participations->where('transport', TransportEnum::BUS)->sum('seats');
        $ownChartCount = $participations->where('transport', TransportEnum::INDIVIDUAL)->count();
        $this->dispatch('updateChart', bus: $busChartCount, own: $ownChartCount);

        return view('livewire.survey-maranata', [
            'participations' => $participations
        ]);
    }
}

