<?php

namespace Jurager\Passport\Console\Commands;

use Illuminate\Support\Facades\Schema;
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
    protected $description = 'Prune expired history entries';

    /**
     * Execute the console command.
     *
     * @param History $history
     * @return bool
     */
    public function handle(History $history): bool
    {

        if ( Schema::hasTable($history->getTable()) ) {

            $history = $history->where('expires_at', '<=', now());
            $counted = $history->count();

            $history->delete();

            $this->info('Successfully pruned '.$counted.' history entries');

            return true;
        }

        return false;
    }
}