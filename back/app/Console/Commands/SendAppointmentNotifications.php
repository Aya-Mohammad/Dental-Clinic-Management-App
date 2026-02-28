<?php

namespace App\Console\Commands;

use App\Models\Appointment;
use App\Models\User;
use App\Models\UserNotification;
use App\Notifications\AppointmentReminderNotification;
use Carbon\Carbon;
use Exception;
use Illuminate\Console\Command;

class SendAppointmentNotifications extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'notify:appointments';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $appointments = Appointment::where('notified_48h', '=', false)
            ->orWhere('notified_2h', '=', false)
            ->get();
        $now = now();
        foreach($appointments as $appointment){
            $appointment_date = Carbon::parse($appointment->date);
            $appointment_time = Carbon::createFromFormat('H:i:s', $appointment->time);
            $appointment_date->setHour($appointment_time->hour)
                ->setMinute($appointment_time->minute)
                ->setSecond($appointment_time->second);
            $user = User::find($appointment->user_id);
            if($user->fcm_token == null){
                continue;
            }
            if($appointment_date->diffInHours($now) <= 48 && !$appointment->notified_48h){
                try{
                    UserNotification::create([
                        'type' => 'appointment',
                        'title' => 'appointment reminder',
                        'messages' => 'your appointment is in 2 days',
                        'is_read' => false,
                        'data' => null,
                        'user_id' => $user->id,
                    ]);
                    $appointment->update(['notified_48h' => true]);
                    $user->notify(new AppointmentReminderNotification(48));
                }
                catch(Exception $e){

                }
            }

            if($appointment_date->diffInHours($now) <= 2 && !$appointment->notified_2h){
                try{
                    UserNotification::create([
                        'type' => 'appointment',
                        'title' => 'appointment reminder',
                        'messages' => 'your appointment is in 2 hours',
                        'is_read' => false,
                        'data' => null,
                        'user_id' => $user->id,
                    ]);
                    $appointment->update(['notified_2h' => true]);
                    $user->notify(new AppointmentReminderNotification(2));
                }
                catch(Exception $e){
                    
                }
            }
        }
        return Command::SUCCESS;
    }
}
