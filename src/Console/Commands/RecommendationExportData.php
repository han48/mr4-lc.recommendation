<?php

namespace Mr4Lc\Recommendation\Console\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Mr4Lc\JsonExporter\File;
use stdClass;

class RecommendationExportData extends Command
{
    use \Mr4Lc\Recommendation\Traits\ConsoleOutput;
    use \Mr4Lc\Recommendation\Traits\FilePath;

    const VERSION = '0.0.1';

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'recommendation:export {tableName} {--chunkSize=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Recommendation export data to JSON';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $tableName = $this->argument('tableName');
        $chunkSize = $this->option('chunkSize');
        return $this->exportData($tableName, $chunkSize);
    }

    public function exportData($tableName, $chunkSize = null)
    {
        $output = config('mr4recommendation.output', null);
        if (!isset($output)) {
            $this->writeOutput('Don\'t have output, please update or add config.');
            return;
        }

        $now = Carbon::now();

        $configs = config('mr4recommendation.mapping.tables.' . $tableName);
        if (!isset($configs)) {
            $this->writeOutput('Don\'t have config, please update or add config.');
            return;
        }
        if (!array_key_exists('order', $configs)) {
            $this->writeOutput('Don\'t have order, please update or add config.');
            return;
        }
        if (!array_key_exists('map', $configs)) {
            $this->writeOutput('Don\'t have map, please update or add config.');
            return;
        }

        if (!file_exists($output)) {
            mkdir($output, 0755, true);
        }
        if (array_key_exists('output', $configs)) {
            $fileName = $configs['output'];
        } else {
            $fileName = $tableName . '.json';
        }
        $fileName = $this->merge_paths($output, $fileName);

        if (!array_key_exists('data_version', $configs)) {
            $version = static::VERSION;
        } else {
            $version = $configs['data_version'];
        }

        $maps = $configs['map'];

        if (!array_key_exists('map_type', $configs)) {
            $configs['map_type'] = [];
        }
        if (!array_key_exists('id', $maps)) {
            $this->writeOutput('Don\'t have map id, please update or add config.');
            return;
        }

        if (!isset($chunkSize)) {
            $chunkSize = config('mr4recommendation.chunkSize', 1000);
        }
        $chunkSize = intval($chunkSize);
        $model = DB::table($tableName);
        if (array_key_exists('status', $configs)) {
            $status = $configs['status'];
            foreach ($status as $key => $value) {
                $model = $model->where($key, $value);
            }
        }

        $model = $model->orderBy($configs['order']);
        $objs = [];

        if (file_exists($fileName)) {
            rename($fileName, $fileName . '.' . $now->timestamp . $this->generateRandomString() . '.bk');
        }

        $count = $model->count();
        $file = new File($fileName);
        $itemCollection = $file->collection('items');
        $index = 0;
        foreach ($model->cursor() as $row) {
            $index++;
            $this->writeOutput("Exporting: $index / $count");
            $obj = new stdClass();
            foreach ($maps as $key => $value) {
                if (is_array($value)) {
                    $type = 'merge';
                    $item = null;
                    if (array_key_exists($key, $configs['map_type'])) {
                        $type = $configs['map_type'][$key];
                    }
                    switch ($type) {
                        case 'object':
                            if (!isset($item)) {
                                $item = new stdClass();
                            }
                            foreach ($value as $column) {
                                $item->{$column} = $row->{$column};
                            }
                            break;
                        default:
                            if (!isset($item)) {
                                $item = "";
                            }
                            $tmp = [];
                            foreach ($value as $column) {
                                $tmp[] = $row->{$column};
                            }
                            $item = implode("_", $tmp);
                            break;
                    }
                    $obj->{$key} = $item;
                } else {
                    $obj->{$key} = $row->{$value};
                }
            }
            $objs[] = $obj;
        }
        $itemCollection->addItems($objs);
        $file->value('created_at', $now->toIso8601String());
        $file->value('version', $version);
        $file->end();
    }
}
