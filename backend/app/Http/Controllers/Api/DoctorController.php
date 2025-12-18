<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Doctor;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DoctorController extends Controller
{
    public function index(): JsonResponse
    {
        $doctors = Doctor::all();

        return response()->json($doctors);
    }

    public function availability(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'date' => 'required|date_format:Y-m-d',
        ]);

        $doctor = Doctor::findOrFail($id);
        $date = $request->input('date');

        $selectedDate = \Carbon\Carbon::parse($date);

        if ($selectedDate->isWeekend()) {
            return response()->json([
                'message' => 'Doctors are only available Monday through Friday.',
                'available_slots' => [],
            ], 422);
        }

        $availableSlots = $this->generateTimeSlots($selectedDate);

        $bookedAppointments = $doctor->scheduledAppointments()
            ->whereDate('start_time', $selectedDate->format('Y-m-d'))
            ->get();

        $bookedSlots = $bookedAppointments->map(function ($appointment) {
            return $appointment->start_time->format('H:i');
        })->toArray();

        $availableSlots = array_filter($availableSlots, function ($slot) use ($bookedSlots) {
            return !in_array($slot, $bookedSlots);
        });

        return response()->json([
            'doctor_id' => $doctor->id,
            'doctor_name' => $doctor->name,
            'date' => $date,
            'available_slots' => array_values($availableSlots),
        ]);
    }

    private function generateTimeSlots(\Carbon\Carbon $date): array
    {
        $slots = [];
        $startTime = $date->copy()->setTime(9, 0, 0);
        $endTime = $date->copy()->setTime(17, 0, 0);

        $current = $startTime->copy();
        while ($current->lt($endTime)) {
            $slots[] = $current->format('H:i');
            $current->addMinutes(30);
        }

        return $slots;
    }
}


