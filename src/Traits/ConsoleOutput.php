<?php

namespace Mr4Lc\Recommendation\Traits;

trait ConsoleOutput
{
    protected $output;

    /**
     * Write output
     *
     * @param [type] $message
     * @param [type] $verbosity
     * @return void
     */
    public function writeOutput($message, $type = 'info', $verbosity = NULL)
    {
        try {
            if (!isset($this->output) || $this->output === null) {
                $this->output = new \Symfony\Component\Console\Output\ConsoleOutput();
            }
            $styled = $type ? "<$type>$message</$type>" : $message;
            $this->output->writeln($styled);
        } catch (\Exception $ex) {
            \Illuminate\Support\Facades\Log::error($ex);
        }
    }
}
