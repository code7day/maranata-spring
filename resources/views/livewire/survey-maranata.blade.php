@php
    use App\Enums\TransportEnum;
@endphp

@push('styles')
<style>
    .btn-gradient-submit {
        background-image: linear-gradient(to right, #3b82f6 0%, #16a34a 51%, #3b82f6 100%);
        background-size: 200% auto;
        transition: 0.5s;
    }
    .btn-gradient-submit:hover {
        background-position: right center;
    }
    .animate-pulse-slow {
        animation: pulse 2.5s cubic-bezier(0.4, 0, 0.6, 1) infinite;
    }
    @keyframes pulse {
        0%, 100% { opacity: 1; }
        50% { opacity: .7; }
    }
    .timer-box {
        background: #1a202c;
        color: white;
        padding: 0.5rem 1rem;
        border-radius: 0.5rem;
        min-width: 60px;
    }
</style>
@endpush

<div x-data="{
        view: 'form',
        isSubmitting: false,
        seats: @entangle('seats'),
        standing: @entangle('standing'),
        baseAvailableSeated: {{ $this->availableBusSeats }},
        baseAvailableStanding: {{ $this->availableStandingSeats }},
        totalFare: 0,
        deadline: '{{ $this->deadlineIsoString }}',
        timeLeft: { hours: '00', minutes: '00', seconds: '00' },
        timerExpired: false,
        initTimer() {
            const endTime = new Date(this.deadline).getTime();
            if (new Date().getTime() > endTime) {
                this.timerExpired = true;
                return;
            }
            const interval = setInterval(() => {
                const now = new Date().getTime();
                const distance = endTime - now;
                if (distance < 0) {
                    clearInterval(interval);
                    this.timerExpired = true;
                    $wire.call('$refresh');
                    return;
                }
                this.timeLeft.hours = String(Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60))).padStart(2, '0');
                this.timeLeft.minutes = String(Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60))).padStart(2, '0');
                this.timeLeft.seconds = String(Math.floor((distance % (1000 * 60)) / 1000)).padStart(2, '0');
            }, 1000);
        }
     }"
     x-init="initTimer()"
     @participation-saved.window="view = 'success'; isSubmitting = false; baseAvailableSeated = $event.detail.availableSeats; baseAvailableStanding = $event.detail.availableStanding; totalFare = $event.detail.totalFare"
     @report-unlocked.window="view = 'report'"
     class="w-full max-w-4xl mx-auto">
    <div class="relative w-full h-48 md:h-48 md:rounded-t-2xl bg-gradient-to-br from-blue-400 to-green-500 flex items-center justify-center text-white overflow-hidden">
        <div class="absolute inset-0 bg-black/30"></div>
        <div class="relative z-10 text-center p-4 -mt-5 mb-5">
            <h1 class="text-3xl md:text-4xl font-bold text-white drop-shadow-lg">Maranata Spring 2025</h1>
            <p class="text-lg text-white/90 mt-1 drop-shadow-md">Distrito Misionero de La Alameda en el  <a href="https://maps.app.goo.gl/gARkbiS1eJzVRr2t8" target="_blank" class="text-yellow-300 underline">Club Portada del Sol</a></p>
        </div>
    </div>

    {{-- Vista del Formulario --}}
    <div x-show="view === 'form'" x-transition>
        <div class="relative z-10 -mt-12">
            <div class="bg-gray-50/80 backdrop-blur-sm shadow-xl rounded-2xl border border-gray-200/50">
                <div class="p-6 sm:p-8">
                    <div class="text-center mb-6">
                        <div x-show="!timerExpired">
                            <h2 class="text-lg font-semibold text-gray-800 mb-2">¡Reserva tu cupo para el bus ahora!</h2>
                            <div class="flex justify-center items-center space-x-2 text-gray-900">
                                <div class="timer-box"><span x-text="timeLeft.hours" class="text-3xl font-bold"></span><div class="text-xs">HRS</div></div>
                                <span class="text-3xl font-bold">:</span>
                                <div class="timer-box"><span x-text="timeLeft.minutes" class="text-3xl font-bold"></span><div class="text-xs">MIN</div></div>
                                <span class="text-3xl font-bold">:</span>
                                <div class="timer-box"><span x-text="timeLeft.seconds" class="text-3xl font-bold"></span><div class="text-xs">SEG</div></div>
                            </div>
                            <p class="text-sm text-gray-600 mt-2">El tiempo para asegurar tu asiento en el bus se acaba.</p>
                        </div>
                        <div x-show="timerExpired" x-cloak>
                             <h2 class="text-xl font-bold text-red-600">El tiempo de reserva para el bus ha terminado.</h2>
                             <p class="text-base text-gray-600">Aún puedes registrar tu participación con transporte individual.</p>
                        </div>
                    </div>

                    <form @submit.prevent="isSubmitting = true; $wire.save()" class="space-y-6">
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                            <div class="space-y-2">
                                <label for="fullName" class="text-base font-medium text-gray-800">Nombre y Apellidos</label>
                                <input wire:model.defer="fullName" id="fullName" type="text" placeholder="Ingresa tu nombre completo" class="h-12 text-base w-full rounded-lg border bg-white border-gray-300 shadow-inner focus:border-blue-500 focus:ring-blue-500 px-4">
                                @error('fullName') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
                            </div>
                            <div class="space-y-2">
                                <label for="cellphone" class="text-base font-medium text-gray-800">Celular (WhatsApp)</label>
                                <input wire:model.defer="cellphone" id="cellphone" type="tel" placeholder="Ej: 987654321" class="h-12 text-base w-full rounded-lg border bg-white border-gray-300 shadow-inner focus:border-blue-500 focus:ring-blue-500 px-4">
                                @error('cellphone') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
                            </div>
                        </div>

                        <div class="space-y-4">
                            <label class="text-base font-medium text-gray-800">Elige tu opción de transporte</label>
                            <div @click="$wire.set('transport', '{{ TransportEnum::BUS->value }}')"
                                class="rounded-xl border-4 transition-all bg-white  {{ $this->isBusDisabled ? 'border-gray-300 !bg-gray-100 cursor-not-allowed opacity-70 pointer-events-none' : 'shadow-md cursor-pointer hover:border-blue-300' }} {{ $transport?->value === TransportEnum::BUS->value ? 'border-blue-500 !bg-blue-50' : 'border-gray-200' }}">
                                <div class="flex flex-col lg:flex-row items-start lg:space-x-4 p-5">
                                    <div class="flex items-start space-x-4 flex-1">
                                        <div class="flex-shrink-0 w-12 h-12 rounded-full flex items-center justify-center {{ $this->isBusDisabled ? 'bg-gray-200' : 'bg-blue-100' }}">
                                            <span class="text-2xl">{{ $this->isBusDisabled ? '🚫' : '🚌' }}</span>
                                        </div>
                                        <div class="flex-1">
                                            <h4 class="font-semibold text-lg text-gray-900 mb-2">{{ $this->isBusDisabled ? TransportEnum::BUS->getLabel().' - AGOTADO' : TransportEnum::BUS->getLabel() }}</h4>
                                            <div class="space-y-2 text-sm {{ $this->isBusDisabled ? 'text-gray-500' : 'text-gray-600' }}">
                                                <div class="flex items-center space-x-2"><span>💰</span><span><strong>S/ 7 soles pasaje sentado</strong></span></div>
                                                <div class="flex items-center space-x-2"><span>💰</span><span><strong>S/ 4 soles pasaje de pie</strong></span></div>
                                                <div class="flex items-center space-x-2"><span>🎟️</span><span><strong>Paga entrada al club</strong> (S/ 10 Adultos + S/ 5 niños)</span></div>
                                                <div class="flex items-center space-x-2"><span>🕐</span><span>Salida desde el parque de la iglesia <strong> a las 8:00 am</strong> </span></div>
                                                <div class="flex items-center space-x-2"><span>🥗</span><span>No olvide llevar su almuerzo</span></div>
                                                <div class="flex items-center space-x-2"><span>❤️</span><span>Viajemos juntos como familia</span></div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="mt-4 grid  lg:grid-cols-1 grid-cols-2 gap-4 w-full lg:mt-0 lg:w-auto lg:flex-1">
                                        <div class="bg-blue-50 border border-blue-200 rounded-lg p-3">
                                            <div class="flex items-center justify-between">
                                                <span class="font-medium text-gray-900 text-sm">Asientos</span>
                                                <div class="text-right">
                                                    <div class="text-2xl font-bold text-blue-600" x-text="`${baseAvailableSeated - ($wire.showSeatsInput ? (seats > 0 ? seats : 0) : 0)}`"></div>
                                                    <div class="text-xs text-gray-500 -mt-1">/ {{ $this->busSeatedCapacity }}</div>
                                                </div>
                                            </div>
                                            <div class="w-full bg-blue-200 rounded-full h-1.5 mt-2">
                                                <div class="bg-blue-500 h-1.5 rounded-full" style="width: {{ $this->busSeatedCapacity > 0 ? (($this->busSeatedCapacity - $this->availableBusSeats) / $this->busSeatedCapacity) * 100 : 0 }}%"></div>
                                            </div>
                                            <p class="text-xs mt-1 text-center font-semibold text-blue-700" x-text="`¡Quedan ${baseAvailableSeated - ($wire.showSeatsInput ? (seats > 0 ? seats : 0) : 0)} asientos!`"></p>
                                        </div>
                                        <div class="bg-green-50 border border-green-200 rounded-lg p-3">
                                            <div class="flex items-center justify-between">
                                                <span class="font-medium text-gray-900 text-sm">De Pie</span>
                                                <div class="text-right">
                                                    <div class="text-2xl font-bold text-green-600" x-text="`${baseAvailableStanding - ($wire.showSeatsInput ? (standing > 0 ? standing : 0) : 0)}`"></div>
                                                    <div class="text-xs text-gray-500 -mt-1">/ {{ $this->busStandingCapacity }}</div>
                                                </div>
                                            </div>
                                            <div class="w-full bg-green-200 rounded-full h-1.5 mt-2">
                                                <div class="bg-green-500 h-1.5 rounded-full" style="width: {{ $this->busStandingCapacity > 0 ? (($this->busStandingCapacity - $this->availableStandingSeats) / $this->busStandingCapacity) * 100 : 0 }}%"></div>
                                            </div>
                                            <p class="text-xs mt-1 text-center font-semibold text-green-700" x-text="`¡Quedan ${baseAvailableStanding - ($wire.showSeatsInput ? (standing > 0 ? standing : 0) : 0)} cupos!`"></p>
                                        </div>
                                    </div>
                                </div>
                            </div>

                        <div x-show="$wire.showSeatsInput" x-transition class="space-y-4">
                            <div class="grid grid-cols-2 gap-4">
                                <div class="space-y-2">
                                    <label for="seats" class="text-base font-medium">¿Cuántos asientos reservados?</label>
                                    <input x-model.number="seats" id="seats" type="number" min="0" max="{{ $this->maxSeatedAllowed }}" class="h-12 text-base pl-4 pr-4 w-full rounded-lg border-2 border-blue-300 shadow-inner focus:border-blue-500 focus:ring-blue-500" @if($this->availableBusSeats <= 0) disabled @endif>
                                    @error('seats') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
                                </div>
                                <div class="space-y-2">
                                    <label for="standing" class="text-base font-medium">¿Cuántos pasajeros de pie?</label>
                                    <input x-model.number="standing" id="standing" type="number" min="0" max="{{ $this->maxStandingAllowed }}" class="h-12 text-base pl-4 pr-4 w-full rounded-lg border-2 border-green-300 shadow-inner focus:border-green-500 focus:ring-green-500" @if($this->availableStandingSeats <= 0) disabled @endif>
                                    @error('standing') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
                                </div>
                            </div>
                            <div x-show="(seats > 0 || standing > 0)" class="text-center p-3 bg-yellow-100 border border-yellow-200 rounded-lg">
                                <p class="font-semibold text-yellow-800">Monto total a pagar por pasajes:
                                    <span class="text-lg font-bold" x-text="`S/ ${( (seats || 0) * 7 + (standing || 0) * 4 ).toFixed(2)}`"></span>
                                </p>
                                <p class="text-sm mt-2 text-gray-700">Puedes yapear al <strong>989 059 322</strong> o pagar en efectivo al hermano <strong>Wilfredo Colque / Wilmer Salcedo</strong>.</p>
                            </div>
                        </div>

                            <div @click="$wire.set('transport', '{{ TransportEnum::INDIVIDUAL->value }}')"
                                class="rounded-xl border-4 transition-all bg-white shadow-md cursor-pointer hover:border-green-300 {{ $transport?->value === TransportEnum::INDIVIDUAL->value ? 'border-green-500 !bg-green-50' : 'border-gray-200' }}">
                                <div class="flex items-start space-x-4 p-5">
                                    <div class="flex-shrink-0 w-12 h-12 bg-green-100 rounded-full flex items-center justify-center"><span class="text-2xl">🚗</span></div>
                                    <div class="flex-1">
                                        <h4 class="font-semibold text-lg text-gray-900 mb-2">{{ TransportEnum::INDIVIDUAL->getLabel() }}</h4>
                                        <div class="space-y-2 text-sm text-gray-600">
                                            <div class="flex items-center space-x-2"><span>🚗</span><span>Llegas directamente al lugar del evento (<a href="https://maps.app.goo.gl/gARkbiS1eJzVRr2t8" target="_blank" class="text-blue-500 underline">Club Portada del Sol</a>)</span> </div>
                                            <div class="flex items-center space-x-2"><span>🎟️</span><span><strong>Paga entrada al club</strong> (S/ 10 Adultos + S/ 5 niños)</span></div>
                                            <div class="flex items-center space-x-2"><span>🕐</span><span>Horario flexible de llegada</span></div>
                                            <div class="flex items-center space-x-2"><span>🥗</span><span>No olvide llevar su almuerzo</span></div>
                                        </div>
                                        <div class="mt-3 text-xs bg-green-100 text-green-800 px-3 py-1 rounded-full inline-block">Mayor flexibilidad personal</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        @error('transport') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror

                        <button type="submit" :disabled="isSubmitting" class="w-full h-12 text-base font-semibold text-white cursor-pointer rounded-lg shadow-md btn-gradient-submit focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 " wire:loading.attr="disabled">
                            <span x-show="!isSubmitting"><span wire:loading.remove wire:target="save">Confirmar Participación</span></span>
                            <span x-show="isSubmitting" wire:loading wire:target="save">Registrando...</span>
                        </button>
                    </form>

                    <div class="mt-6 pt-6 border-t border-gray-200 text-center">
                        <a href="#" @click.prevent="$wire.refreshReportData(); $wire.isReportUnlocked ? view = 'report' : view = 'password'" class="text-sm text-blue-600 hover:text-blue-500 underline underline-offset-4">Ver reporte de participación →</a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div x-show="view === 'password'" x-cloak x-transition>
        <div class="relative z-10 -mt-12">
            <div class="bg-white/80 backdrop-blur-sm shadow-xl rounded-2xl border border-gray-200/50">
                <div class="p-8 sm:p-12 text-center">
                    <div class="mx-auto flex-shrink-0 flex items-center justify-center h-16 w-16 rounded-full bg-blue-100">
                        <svg class="h-8 w-8 text-blue-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 1 0-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 0 0 2.25-2.25v-6.75a2.25 2.25 0 0 0-2.25-2.25H6.75a2.25 2.25 0 0 0-2.25 2.25v6.75a2.25 2.25 0 0 0 2.25 2.25Z" /></svg>
                    </div>
                    <h3 class="mt-5 text-2xl leading-6 font-bold text-gray-900">Acceso al Reporte</h3>
                    <p class="mt-2 text-base text-gray-600">Por favor, ingresa la clave para ver las estadísticas.</p>
                    <div class="mt-6 max-w-sm mx-auto">
                        <input wire:model.defer="reportPassword" @keydown.enter.prevent="$wire.checkPassword()" type="password" placeholder="Ingresa la clave" class="h-12 text-base text-center w-full rounded-lg border border-gray-300 shadow-inner focus:border-blue-500 focus:ring-blue-500 px-4">
                        @if($passwordError)
                            <p class="text-red-500 text-sm mt-2">{{ $passwordError }}</p>
                        @endif
                    </div>
                    <div class="mt-8 flex justify-center space-x-4">
                        <button wire:click="checkPassword" wire:loading.attr="disabled" type="button" class="inline-flex justify-center rounded-lg border border-transparent shadow-sm px-6 py-3 bg-blue-600 text-base font-medium text-white hover:bg-blue-700 focus:outline-none">
                            <span wire:loading.remove wire:target="checkPassword">Confirmar</span>
                            <span wire:loading wire:target="checkPassword">Verificando...</span>
                        </button>
                        <button @click="view = 'form'; isSubmitting = false" wire:click="resetForm" type="button" class="inline-flex justify-center rounded-lg border border-gray-300 shadow-sm px-6 py-3 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none">
                            Cancelar
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div x-show="view === 'success'" x-cloak x-transition>
        <div class="relative z-10 -mt-12">
            <div class="bg-white/80 backdrop-blur-sm shadow-xl rounded-2xl border border-gray-200/50">
                <div class="p-8 sm:p-12 text-center">
                    <div class="mx-auto flex-shrink-0 flex items-center justify-center h-16 w-16 rounded-full bg-green-100">
                        <svg class="h-8 w-8 text-green-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
                    </div>
                    <h3 class="mt-5 text-2xl leading-6 font-bold text-gray-900">¡Participación Confirmada!</h3>
                    <div class="mt-3 space-y-3">
                        <p class="text-base text-gray-600">Tu registro ha sido exitoso. ¡Que Dios nos bendiga en este día especial de sábado!</p>
                        <div x-show="totalFare > 0" class="p-4 bg-yellow-100 border border-yellow-200 rounded-lg">
                            <p class="font-semibold text-yellow-800">Monto a pagar por pasajes: <span class="text-lg font-bold" x-text="`S/ ${totalFare.toFixed(2)}`"></span></p>
                            <p class="text-sm mt-2 text-gray-700">Puedes yapear al <strong>989 059 322</strong> o pagar en efectivo al hermano <strong>Wilfredo Colque / Wilmer Salcedo</strong>.</p>
                        </div>
                    </div>
                    <div class="mt-8 flex justify-center space-x-4">
                        <button @click="$wire.refreshReportData(); $wire.isReportUnlocked ? view = 'report' : view = 'password'" type="button" class="inline-flex justify-center rounded-lg border border-transparent shadow-sm px-6 py-3 bg-blue-600 text-base font-medium text-white hover:bg-blue-700 focus:outline-none">
                            Ver Reporte
                        </button>
                        <button @click="view = 'form'; isSubmitting = false" wire:click="resetForm" type="button" class="inline-flex justify-center rounded-lg border border-gray-300 shadow-sm px-6 py-3 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none">
                            Registrar Otro
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Vista de Reporte --}}
    <div x-show="view === 'report'" x-cloak>
        <div class="relative z-10 -mt-12">
            <div class="bg-white p-8 rounded-2xl shadow-lg">
                <div class="text-center mb-8">
                    <a href="#" @click.prevent="view = 'form'; isSubmitting = false" wire:click.prevent="resetForm" class="text-sm font-medium text-blue-600 hover:text-blue-500 absolute top-8 left-8">← Volver al Formulario</a>
                    <h2 class="text-3xl font-bold tracking-tight mt-5">Reporte de Participación</h2>
                    <p class="mt-2 text-lg text-gray-600">Maranata Spring 2025</p>
                </div>

                <div class="grid grid-cols-2 md:grid-cols-4 gap-6 mb-8 text-center">
                    <div class="bg-gray-100 p-4 rounded-lg shadow-sm"><p class="text-3xl font-bold">{{ $this->totalParticipants }}</p><p class="text-sm text-gray-600">Total Participantes</p></div>
                    <div class="bg-gray-100 p-4 rounded-lg shadow-sm"><p class="text-3xl font-bold">{{ $this->totalBusParticipants }}</p><p class="text-sm text-gray-600">Transporte Comunitario</p></div>
                    <div class="bg-gray-100 p-4 rounded-lg shadow-sm"><p class="text-3xl font-bold">{{ $this->totalOwnParticipants }}</p><p class="text-sm text-gray-600">Transporte Personal</p></div>
                    <div class="bg-gray-100 p-4 rounded-lg shadow-sm"><p class="text-3xl font-bold">{{ $this->totalRegistrations }}</p><p class="text-sm text-gray-600">Total Registros</p></div>
                </div>

                <div class="bg-gray-50 border border-gray-200 rounded-lg p-6 mb-8">
                    <h3 class="text-lg font-semibold text-gray-800 mb-4 flex items-center"><span class="text-xl mr-3">🚌</span> Información para Contratar Bus</h3>
                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 text-center">
                        <div class="bg-blue-100 text-blue-800 p-4 rounded-lg flex flex-col justify-between">
                            <div><p class="text-3xl font-bold">{{ $this->totalBusSeated }}</p><p class="font-medium">Asientos Necesarios</p></div>
                            <p class="text-xs mt-1">({{ $this->totalBusSeated }} x S/7 = S/{{ number_format($this->seatedBusIncome, 2) }})</p>
                        </div>
                        <div class="bg-cyan-100 text-cyan-800 p-4 rounded-lg flex flex-col justify-between">
                            <div><p class="text-3xl font-bold">{{ $this->totalBusStanding }}</p><p class="font-medium">Pasajeros de Pie</p></div>
                            <p class="text-xs mt-1">({{ $this->totalBusStanding }} x S/4 = S/{{ number_format($this->standingBusIncome, 2) }})</p>
                        </div>
                        <div class="bg-green-100 text-green-800 p-4 rounded-lg"><p class="text-3xl font-bold">{{ $this->busesNeeded }}</p><p class="font-medium">Bus(es) de {{ $this->busSeatedCapacity }} asientos</p></div>
                        <div class="bg-yellow-100 text-yellow-800 p-4 rounded-lg"><p class="text-3xl font-bold">S/ {{ number_format($this->busIncome, 2) }}</p><p class="font-medium">Monto Recaudado</p></div>
                    </div>
                </div>

                <div class="bg-white border border-gray-200 rounded-lg p-6">
                     <div class="flex flex-col sm:flex-row justify-between items-center mb-4">
                        <h3 class="text-lg font-semibold text-gray-800">Lista de Participantes Registrados</h3>
                        <div class="flex space-x-1 bg-gray-200 p-1 rounded-lg mt-2 sm:mt-0">
                            <button wire:click.prevent="filter('all')" class="px-3 py-1 text-sm rounded-md transition {{ $filterBy === 'all' ? 'bg-white shadow' : 'hover:bg-gray-300' }}">Todos</button>
                            <button wire:click.prevent="filter('bus')" class="px-3 py-1 text-sm rounded-md transition {{ $filterBy === 'bus' ? 'bg-white shadow' : 'hover:bg-gray-300' }}">En Bus</button>
                            <button wire:click.prevent="filter('individual')" class="px-3 py-1 text-sm rounded-md transition {{ $filterBy === 'individual' ? 'bg-white shadow' : 'hover:bg-gray-300' }}">Individual</button>
                        </div>
                     </div>
                     <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        <a href="#" wire:click.prevent="sortBy('full_name')" class="flex items-center space-x-1">
                                            <span>Nombre</span>
                                            @if($sortBy === 'full_name')<span class="text-gray-900">@if($sortDirection === 'asc') &#9650; @else &#9660; @endif</span>@endif
                                        </a>
                                    </th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Detalle de Viaje</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Transporte</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        <a href="#" wire:click.prevent="sortBy('created_at')" class="flex items-center space-x-1">
                                            <span>Fecha</span>
                                            @if($sortBy === 'created_at')<span class="text-gray-900">@if($sortDirection === 'asc') &#9650; @else &#9660; @endif</span>@endif
                                        </a>
                                    </th>
                                    <th scope="col" class="relative px-6 py-3"><span class="sr-only">Anular</span></th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @forelse($participations as $p)
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{{ $p->full_name }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        @if($p->transport === TransportEnum::BUS)
                                            <span>{{ $p->seats }} sentados, {{ $p->standing }} de pie</span>
                                            <span class="block text-xs text-gray-500">(Total: S/ {{ number_format(($p->seats * 7) + ($p->standing * 4), 2) }})</span>
                                        @else
                                            <span>1 participante</span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full {{ $p->transport === TransportEnum::BUS ? 'bg-blue-100 text-blue-800' : 'bg-green-100 text-green-800' }}">
                                            {{ $p->transport->getLabel() }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ \Carbon\Carbon::parse($p->created_at)->format('d/m/Y, h:i a') }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                        <button wire:click="deleteParticipation({{ $p->id }})" wire:confirm="¿Estás seguro de que quieres anular este registro?" class="text-red-600 hover:text-red-900 cursor-pointer">Anular</button>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="5" class="px-6 py-4 text-center text-sm text-gray-500">No hay registros que coincidan con el filtro.</td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                     </div>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
    document.addEventListener('livewire:load', function () {
        const ctx = document.getElementById('transportChart')?.getContext('2d');
        if (!ctx) return;
        let chart;

        const initOrUpdateChart = (bus, own) => {
            const data = {
                labels: ['Transporte en Bus', 'Transporte Individual'],
                datasets: [{
                    label: '# de Participantes',
                    data: [bus, own],
                    backgroundColor: [ 'rgba(59, 130, 246, 0.5)', 'rgba(16, 185, 129, 0.5)' ],
                    borderColor: [ 'rgba(59, 130, 246, 1)', 'rgba(16, 185, 129, 1)' ],
                    borderWidth: 1
                }]
            };

            if (chart) {
                chart.data.datasets[0].data[0] = bus;
                chart.data.datasets[0].data[1] = own;
                chart.update();
            } else {
                chart = new Chart(ctx, {
                    type: 'bar',
                    data: data,
                    options: {
                        scales: { y: { beginAtZero: true, ticks: { stepSize: 1 } } },
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: { legend: { position: 'top' }, title: { display: false } }
                    },
                });
            }
        };

        let initialBus = @json($this->totalBusParticipants);
        let initialOwn = @json($this->totalOwnParticipants);
        initOrUpdateChart(initialBus, initialOwn);

        Livewire.on('updateChart', ({ bus, own }) => {
            initOrUpdateChart(bus, own);
        });
    });
    </script>
@endpush

