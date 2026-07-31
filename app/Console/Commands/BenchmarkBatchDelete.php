<?php

namespace App\Console\Commands;

use App\Models\Event;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class BenchmarkBatchDelete extends Command
{
    protected $signature = 'benchmark:batch-delete';
    protected $description = 'Benchmark the batch delete function';

    public function handle()
    {
        $eventsCreated = Event::factory()->count(2)->create();
        $ids = $eventsCreated->pluck('id')->toArray();
        $events = Event::whereIn('id', $ids)->get();

        DB::listen(function($sql) {
            $this->info("Query: " . $sql->sql);
        });

        $this->info("Before activity log count: " . DB::table('activity_log')->count());

        $deletableIds = [];
        foreach ($events as $event) {
            $deletableIds[] = $event->id;
        }

        $now = now();
        Event::whereIn('id', $deletableIds)->delete();

        $this->info("After activity log count: " . DB::table('activity_log')->count());
        $this->info("Recent activity logs:");
        $logs = DB::table('activity_log')->orderBy('id', 'desc')->take(2)->get();
        foreach ($logs as $log) {
            $this->info($log->description . ' ' . $log->event . ' ' . $log->properties);
        }

        Event::whereIn('id', $ids)->forceDelete();
    }
}
