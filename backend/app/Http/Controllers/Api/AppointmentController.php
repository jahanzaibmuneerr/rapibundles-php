<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\Doctor;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

class AppointmentController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'doctor_id' => 'required|exists:doctors,id',
            'patient_name' => 'required|string|max:255',
            'start_time' => 'required|date_format:Y-m-d H:i',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $doctorId = $request->input('doctor_id');
        $patientName = $request->input('patient_name');
        $startTimeInput = $request->input('start_time');

        try {
            $startTime = Carbon::parse($startTimeInput);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Invalid start_time format. Use Y-m-d H:i format.',
            ], 422);
        }

        if (!in_array($startTime->format('i'), ['00', '30'])) {
            return response()->json([
                'message' => 'Appointments must be booked in 30-minute slots (e.g., 09:00, 09:30).',
            ], 422);
        }

        $hour = (int) $startTime->format('H');
        if ($hour < 9 || $hour >= 17) {
            return response()->json([
                'message' => 'Appointments can only be booked between 09:00 and 17:00.',
            ], 422);
        }

        if ($startTime->isWeekend()) {
            return response()->json([
                'message' => 'Doctors are only available Monday through Friday.',
            ], 422);
        }

        $endTime = $startTime->copy()->addMinutes(30);

        try {
            $appointment = DB::transaction(function () use ($doctorId, $patientName, $startTime, $endTime) {
                $existingAppointment = Appointment::where('doctor_id', $doctorId)
                    ->where('status', 'scheduled')
                    ->where(function ($query) use ($startTime, $endTime) {
                        $query->where('start_time', '<', $endTime)
                              ->where('end_time', '>', $startTime);
                    })
                    ->lockForUpdate()
                    ->first();

                if ($existingAppointment) {
                    throw new \Exception('Slot was just booked. Please choose another time.');
                }

                $appointment = Appointment::create([
                    'doctor_id' => $doctorId,
                    'patient_name' => $patientName,
                    'start_time' => $startTime,
                    'end_time' => $endTime,
                    'status' => 'scheduled',
                ]);

                return $appointment;
            });

            $appointment->load('doctor');

            return response()->json([
                'message' => 'Appointment booked successfully',
                'appointment' => [
                    'id' => $appointment->id,
                    'doctor_id' => $appointment->doctor_id,
                    'doctor_name' => $appointment->doctor->name,
                    'patient_name' => $appointment->patient_name,
                    'start_time' => $appointment->start_time->format('Y-m-d H:i:s'),
                    'end_time' => $appointment->end_time->format('Y-m-d H:i:s'),
                    'status' => $appointment->status,
                ],
            ], 201);

        } catch (\Exception $e) {
            if (str_contains($e->getMessage(), 'Slot was just booked')) {
                return response()->json([
                    'message' => 'Slot was just booked. Please choose another time.',
                    'error' => 'CONFLICT',
                ], 409);
            }

            if (str_contains($e->getMessage(), 'unique_doctor_time_slot') || 
                str_contains($e->getMessage(), 'duplicate key')) {
                return response()->json([
                    'message' => 'Slot was just booked. Please choose another time.',
                    'error' => 'CONFLICT',
                ], 409);
            }

            return response()->json([
                'message' => 'An error occurred while booking the appointment.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}

