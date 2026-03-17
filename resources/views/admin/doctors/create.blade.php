<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Create Doctor') }}
        </h2>
    </x-slot>

    <div class="py-6">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    @if ($errors->any())
                        <div class="mb-4">
                            <div class="font-medium text-red-600">{{ __('Whoops! Something went wrong.') }}</div>
                            <ul class="mt-3 list-disc list-inside text-sm text-red-600">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <form method="POST" action="{{ route('admin.doctors.store') }}" class="space-y-4">
                        @csrf

                        <div>
                            <x-input-label for="name" :value="__('Name')" />
                            <x-text-input id="name" name="name" type="text" class="mt-1 block w-full"
                                          :value="old('name')" required autofocus />
                        </div>

                        <div>
                            <x-input-label for="email" :value="__('Email')" />
                            <x-text-input id="email" name="email" type="email" class="mt-1 block w-full"
                                          :value="old('email')" required />
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <x-input-label for="password" :value="__('Password')" />
                                <x-text-input id="password" name="password" type="password" class="mt-1 block w-full"
                                              required />
                            </div>
                            <div>
                                <x-input-label for="password_confirmation" :value="__('Confirm Password')" />
                                <x-text-input id="password_confirmation" name="password_confirmation" type="password"
                                              class="mt-1 block w-full" required />
                            </div>
                        </div>

                        <div>
                            <x-input-label for="clinic_id" :value="__('Clinic')" />
                            <select id="clinic_id" name="clinic_id"
                                    class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                                @foreach($clinics as $clinic)
                                    <option value="{{ $clinic->id }}" @selected(old('clinic_id') == $clinic->id)>
                                        {{ $clinic->name_clinic }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <x-input-label for="specialization_id" :value="__('Specialization')" />
                            <select id="specialization_id" name="specialization_id"
                                    class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                                @foreach($specializations as $specialization)
                                    <option value="{{ $specialization->id }}" @selected(old('specialization_id') == $specialization->id)>
                                        {{ $specialization->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <x-input-label for="clinic_address" :value="__('Clinic Address')" />
                            <x-text-input id="clinic_address" name="clinic_address" type="text" class="mt-1 block w-full"
                                          :value="old('clinic_address')" required />
                        </div>

                        <div>
                            <x-input-label for="license_number" :value="__('License Number')" />
                            <x-text-input id="license_number" name="license_number" type="text" class="mt-1 block w-full"
                                          :value="old('license_number')" required />
                        </div>

                        <div>
                            <x-input-label for="bio" :value="__('About / Bio')" />
                            <textarea id="bio" name="bio" rows="4"
                                      class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">{{ old('bio') }}</textarea>
                        </div>

                        <div>
                            <x-input-label for="session_price" :value="__('Session Price')" />
                            <x-text-input id="session_price" name="session_price" type="number" step="0.01"
                                          class="mt-1 block w-full" :value="old('session_price')" required />
                        </div>

                        <div class="flex justify-end">
                            <x-primary-button>
                                {{ __('Create Doctor') }}
                            </x-primary-button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>

