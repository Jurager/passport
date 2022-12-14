<?php

namespace Jurager\Passport\Console\Commands;

use Jurager\Passport\Models\History;
use Illuminate\Console\Command;

class Prune extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'history:prune';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send a marketing email to a user';

    /**
     * Execute the console command.
     *
     * @param History $history
     * @return mixed
     */
    public function handle(History $history): mixed
    {
        return $history->where('expires_at', '<=', now())->delete();
    }
}