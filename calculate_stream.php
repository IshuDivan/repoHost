<?php
date_default_timezone_set('UTC');

function get_last_stream($day, $stream_time) {
    $now = new DateTime();
    $days_behind = ($now->format('w') - $day + 7) % 7;
    $last_stream_this_week = $now->modify("-$days_behind days");
    $last_stream_previous_week = clone $last_stream_this_week;
    $last_stream_previous_week->modify('-1 week');

    list($hour, $minute) = explode(':', $stream_time);
    $last_stream_this_week->setTime($hour, $minute);
    $last_stream_previous_week->setTime($hour, $minute);

    return [$last_stream_this_week, $last_stream_previous_week];
}

function get_next_stream($day, $stream_time) {
    $now = new DateTime();
    $days_ahead = ($day - $now->format('w') + 7) % 7;
    $next_stream = clone $now;
    $next_stream->modify("+$days_ahead days");

    list($hour, $minute) = explode(':', $stream_time);
    $next_stream->setTime($hour, $minute);

    return $next_stream;
}

function calculate_time_passed($last_time) {
    $now = new DateTime();
    $interval = $now->diff($last_time);
    return $interval->h + ($interval->i / 60);
}

function calculate_time_until($next_time) {
    $now = new DateTime();
    $interval = $now->diff($next_time);
    return $interval->h + ($interval->i / 60);
}

function get_weekday_name($day) {
    $weekdays = ["Sunday", "Monday", "Tuesday", "Wednesday", "Thursday", "Friday", "Saturday"];
    return $weekdays[$day];
}

function get_weekday_number($weekday_name) {
    $weekdays = [
        "Sunday" => 0,
        "Monday" => 1,
        "Tuesday" => 2,
        "Wednesday" => 3,
        "Thursday" => 4,
        "Friday" => 5,
        "Saturday" => 6
    ];
    return $weekdays[$weekday_name];
}

function main() {
    $stream_days = [
        "Tuesday" => "20:00",
        "Wednesday" => "20:00",
        "Thursday" => "20:00",
        "Friday" => "20:00",
        "Saturday" => "20:00"
    ];
    $last_stream_length = 4.5;

    $last_streams = [];
    foreach ($stream_days as $day_name => $stream_time) {
        $day_number = get_weekday_number($day_name);
        list($last_this_week, $last_previous_week) = get_last_stream($day_number, $stream_time);

        if ($last_this_week < new DateTime()) {
            $last_streams[] = $last_this_week;
        }
        if ($last_previous_week < new DateTime()) {
            $last_streams[] = $last_previous_week;
        }
    }

    $last_stream = !empty($last_streams) ? max($last_streams) : null;
    $next_stream = null;

    foreach ($stream_days as $day_name => $stream_time) {
        $day_number = get_weekday_number($day_name);
        $next_stream_temp = get_next_stream($day_number, $stream_time);
        if ($next_stream_temp > new DateTime()) {
            if ($next_stream === null || $next_stream_temp < $next_stream) {
                $next_stream = $next_stream_temp;
            }
        }
    }

    $output = "";
    if ($last_stream) {
        $now = new DateTime();
        if ($last_stream <= $now && $now <= $last_stream->modify("+$last_stream_length hours")) {
            $time_since_last_stream = calculate_time_passed($last_stream);
            $output = "(Probably) Currently streaming! Time since started: " . number_format($time_since_last_stream, 2) . " hours";
        } else {
            $last_stream_waiting = clone $last_stream;
            $last_stream_waiting->modify("+$last_stream_length hours");
            $done = calculate_time_passed($last_stream_waiting);
            $to_be_done = calculate_time_until($next_stream);

            $total = $done + $to_be_done;

            $output .= "Time passed since last Stream: " . number_format($done, 2) . " hours<br>";
            $output .= "Time until next Stream: " . number_format($to_be_done, 2) . " hours<br>";
            $output .= "Total wait time: " . number_format($total, 2) . " hours<br>";
            $output .= "Next Stream is on " . get_weekday_name($next_stream->format('w'));
        }
    } else {
        $output = "No previous streams found.";
    }

    return $output;
}

echo main();
?>