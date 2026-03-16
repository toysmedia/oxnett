<?php
namespace App\Console\Commands;

use App\Models\Subscriber;
use App\Services\RadiusService;
use Illuminate\Console\Command;

class ExpireUsers extends Command
{
    protected $signature   = 'app:expire-users';
    protected $description = 'Suspend expired subscribers and remove their RADIUS entries';

    public function handle(RadiusService $radius): int
    {
        $expired = Subscriber::where('status', 'active')
            ->whereNotNull('expires_at')
            ->where('expires_at', '<', now())
            ->get();

        if ($expired->isEmpty()) {
            $this->info('No expired subscribers found.');
            return 0;
        }

        $count = 0;
        foreach ($expired as $subscriber) {
            try {
                $radius->suspendUser($subscriber->username);
                $subscriber->update(['status' => 'expired']);
                $count++;
                $this->line("Expired: {$subscriber->username}");
            } catch (\Exception $e) {
                $this->error("Failed for {$subscriber->username}: {$e->getMessage()}");
            }
        }

        $this->info("Processed {$count} expired subscriber(s).");
        return 0;
    }
}
