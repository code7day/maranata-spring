<?php

namespace App\Livewire;

use App\Enums\TransportEnum;
use App\Models\Participation;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\Attributes\Computed;
use Illuminate\Validation\Rules\Enum as EnumRule;
use Carbon\Carbon;

class SurveyMaranata extends Component
{
    public $fullName = '';
    public $cellphone = '';
    public ?TransportEnum $transport = null;
    public $seats = 1;

    public $participations = [];

    public bool $showSeatsInput = false;

    public int $busCapacity = 45;
    public int $maxSeatsPerReservation = 7;

    public string $reportPassword = '';
    public ?string $passwordError = null;

    public string $deadlineIsoString;

    public function mount()
    {
        // CORRECCIÓN: Se especifica la zona horaria 'America/Lima' al crear la fecha.
        // Esto asegura que la fecha límite sea a las 2:00 PM hora de Perú.
        $this->deadlineIsoString = Carbon::today('America/Lima')->setTime(14, 0, 0)->toIso8601String();
        $this->loadParticipations();
    }

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
        return min($this->maxSeatsPerReservation, $this->availableBusSeats);
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

    public function loadParticipations()
    {
        $this->participations = Participation::orderBy('created_at', 'desc')->get();
        $this->dispatch('updateChart', bus: $this->busParticipants, own: $this->ownParticipants);
    }

    public function checkPassword()
    {
        if ($this->reportPassword === 'mrnt$2025') {
            $this->passwordError = null;
            $this->reportPassword = '';
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

        $this->loadParticipations();

        $this->dispatch('participation-saved', availableSeats: $this->availableBusSeats);

        $this->resetForm();
    }

    #[Computed]
    public function totalParticipants()
    {
        $total = 0;
        foreach ($this->participations as $participation) {
            if ($participation->transport === TransportEnum::INDIVIDUAL) {
                $total += 1;
            } elseif ($participation->transport === TransportEnum::BUS) {
                $total += (int) $participation->seats;
            }
        }
        return $total;
    }

    #[Computed]
    public function busParticipants()
    {
        return $this->participations->where('transport', TransportEnum::BUS)->sum('seats');
    }

    #[Computed]
    public function ownParticipants()
    {
        return $this->participations->where('transport', TransportEnum::INDIVIDUAL)->count();
    }

    #[Computed]
    public function busSeats()
    {
        return $this->busParticipants;
    }

    #[Computed]
    public function availableBusSeats()
    {
        return max(0, $this->busCapacity - $this->busParticipants);
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
        if ($this->busParticipants <= 0) {
            return 0;
        }
        return ceil($this->busParticipants / $this->busCapacity);
    }

    #[Computed]
    public function busIncome()
    {
        $passagePrice = 10;
        return $this->busParticipants * $passagePrice;
    }

    #[Layout('components.layouts.web')]
    public function render()
    {
        return view('livewire.survey-maranata');
    }
}
