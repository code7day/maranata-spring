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
    public $standing = 0;

    public bool $showSeatsInput = false;

    public int $busSeatedCapacity = 30;
    public int $busStandingCapacity = 10;
    public int $maxSeatsPerReservation = 7;

    public string $reportPassword = '';
    public ?string $passwordError = null;

    public string $deadlineIsoString;
    public bool $isReportUnlocked = false;

    public string $filterBy = 'all';
    public string $sortBy = 'created_at';
    public string $sortDirection = 'desc';

    public function mount()
    {
        $this->deadlineIsoString = Carbon::today('America/Lima')->setTime(17, 00, 0)->toIso8601String();

        if (Session::get('report_unlocked')) {
            $this->isReportUnlocked = true;
        }
    }

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

    public function updatedTransport(?TransportEnum $value)
    {
        $this->showSeatsInput = ($value === TransportEnum::BUS);
    }

    public function resetForm()
    {
        $this->reset(['fullName', 'cellphone', 'transport', 'seats', 'standing', 'showSeatsInput', 'reportPassword', 'passwordError']);
        $this->resetErrorBag();
    }

    public function refreshReportData()
    {
        unset($this->allParticipations);
    }

    #[Computed]
    public function maxSeatedAllowed()
    {
        return min($this->maxSeatsPerReservation, $this->availableBusSeats());
    }

    #[Computed]
    public function maxStandingAllowed()
    {
        return min($this->maxSeatsPerReservation, $this->availableStandingSeats());
    }

    protected function rules()
    {
        return [
            'fullName' => 'required|string|min:3|max:255',
            'cellphone' => 'required|string|digits:9',
            'transport' => ['required', new EnumRule(TransportEnum::class)],
            'seats' => ['required_if:showSeatsInput,true', 'integer', 'min:0', 'max:' . $this->maxSeatedAllowed],
            'standing' => ['required_if:showSeatsInput,true', 'integer', 'min:0', 'max:' . $this->maxStandingAllowed],
        ];
    }

    protected $messages = [
        'fullName.required' => 'Tu nombre completo es necesario.',
        'cellphone.required' => 'Tu número de celular es necesario.',
        'cellphone.digits' => 'El celular debe tener 9 dígitos.',
        'transport.required' => 'Por favor, elige una opción de transporte.',
        'seats.min' => 'El número de asientos no puede ser negativo.',
        'seats.max' => 'Solo puedes reservar un máximo de :max asientos.',
        'standing.min' => 'El número de pasajeros de pie no puede ser negativo.',
        'standing.max' => 'Solo puedes reservar un máximo de :max cupos de pie.',
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
        if ($this->transport === TransportEnum::BUS && ((int)$this->seats + (int)$this->standing) < 1) {
            $this->addError('seats', 'Debes reservar al menos un cupo (sentado o de pie).');
            return;
        }
        $this->validate();

        $seatsToSave = 0;
        $standingToSave = 0;
        $totalFare = 0;

        if ($this->transport instanceof TransportEnum && $this->transport === TransportEnum::BUS) {
            $seatsToSave = (int) $this->seats;
            $standingToSave = (int) $this->standing;
            $totalFare = ($seatsToSave * 6) + ($standingToSave * 3);
        }

        Participation::create([
            'full_name' => $this->fullName,
            'cellphone' => $this->cellphone,
            'transport' => $this->transport,
            'seats' => $seatsToSave,
            'standing' => $standingToSave,
        ]);

        $this->dispatch('participation-saved',
            availableSeats: $this->availableBusSeats(),
            availableStanding: $this->availableStandingSeats(),
            totalFare: $totalFare // Se envía el total a pagar
        );
        $this->resetForm();
    }

    // --- INDICADORES GLOBALES (NO CAMBIAN CON FILTROS) ---
    #[Computed(persist: true)]
    public function allParticipations()
    {
        return Participation::all();
    }

    #[Computed]
    public function totalParticipants()
    {
        return $this->allParticipations->sum(function ($p) {
            if ($p->transport === TransportEnum::INDIVIDUAL) return 1;
            return (int)$p->seats + (int)$p->standing;
        });
    }

    #[Computed]
    public function totalBusParticipants()
    {
        return (int)$this->totalBusSeated + (int)$this->totalBusStanding;
    }

    #[Computed]
    public function totalBusSeated()
    {
        return $this->allParticipations->where('transport', TransportEnum::BUS)->sum('seats');
    }

    #[Computed]
    public function totalBusStanding()
    {
        return $this->allParticipations->where('transport', TransportEnum::BUS)->sum('standing');
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
    public function availableBusSeats()
    {
        return max(0, $this->busSeatedCapacity - $this->totalBusSeated);
    }

    #[Computed]
    public function availableStandingSeats()
    {
        return max(0, $this->busStandingCapacity - $this->totalBusStanding);
    }

    #[Computed]
    public function isBusBookingOver()
    {
        return now()->gt(Carbon::parse($this->deadlineIsoString));
    }

    #[Computed]
    public function isBusDisabled()
    {
        return ($this->availableBusSeats <= 0 && $this->availableStandingSeats <= 0) || $this->isBusBookingOver;
    }

    #[Computed]
    public function busesNeeded()
    {
        if ($this->totalBusSeated <= 0) { return 0; }
        return ceil($this->totalBusSeated / $this->busSeatedCapacity);
    }

    #[Computed]
    public function seatedBusIncome()
    {
        $seatedPrice = 6;
        return $this->totalBusSeated * $seatedPrice;
    }

    #[Computed]
    public function standingBusIncome()
    {
        $standingPrice = 3;
        return $this->totalBusStanding * $standingPrice;
    }

    #[Computed]
    public function busIncome()
    {
        return $this->seatedBusIncome + $this->standingBusIncome;
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

        $busChartCount = $participations->where('transport', TransportEnum::BUS)->sum(fn($p) => $p->seats + $p->standing);
        $ownChartCount = $participations->where('transport', TransportEnum::INDIVIDUAL)->count();
        $this->dispatch('updateChart', bus: $busChartCount, own: $ownChartCount);

        return view('livewire.survey-maranata', [
            'participations' => $participations
        ]);
    }
}

