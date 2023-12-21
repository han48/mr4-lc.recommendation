<?php

namespace Mr4Lc\Recommendation\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use JsonMachine\Items;
use Mr4Lc\Recommendation\Similarity\ItemSimilarity;
use stdClass;

class RecommendationCreate extends Command
{
    use \Mr4Lc\Recommendation\Traits\ConsoleOutput;
    use \Mr4Lc\Recommendation\Traits\FilePath;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'recommendation:create {tableName} {--id=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $tableName = $this->argument('tableName');
        $product_id = $this->option('id');
        return $this->createMatrix($tableName, $product_id);
    }

    public function createMatrix($tableName, $product_id = null)
    {
        $configs = config('mr4recommendation.mapping.tables.' . $tableName);
        $output = config('mr4recommendation.output', null);
        if (!isset($output)) {
            $this->writeOutput('Don\'t have output, please update or add config.');
            return;
        }
        if (array_key_exists('output', $configs)) {
            $fileName = $configs['output'];
        } else {
            $fileName = $tableName . '.json';
        }
        $fileName = $this->merge_paths($output, $fileName);
        if (!file_exists($fileName)) {
            $exporter = new RecommendationExportData();
            $exporter->exportData($tableName);
        }
        if (array_key_exists('class', $configs)) {
            $classSimilarity = $configs['class'];
        } else {
            $classSimilarity = ItemSimilarity::class;
        }
        $productSimilarity = new $classSimilarity($fileName, ['pointer' => '/items']);
        $similarityMatrixFileNameTemplate = $productSimilarity->calculateSimilarityMatrix($product_id);

        return $similarityMatrixFileNameTemplate;
    }

    public static function GetSimilarityItems($tableName, $product_id, $page = null, $perPage = null, $pagePrefix = 'page')
    {
        $routePath = (empty($_SERVER['HTTPS']) ? 'http' : 'https') . "://" . $_SERVER['HTTP_HOST'] . strtok($_SERVER['REQUEST_URI'], '?');
        $params = request()->all();
        $perPageKey = $pagePrefix === 'page' ? 'perPage' : $pagePrefix . 'perPage';
        if (array_key_exists($pagePrefix, $params)) {
            unset($params[$pagePrefix]);
        }
        if (!isset($page)) {
            if (request()->has($pagePrefix)) {
                $page = request()->get($pagePrefix);
            } else {
                $page = 1;
            }
        }
        $page = $page - 1;
        if (!isset($perPage)) {
            if (request()->has($perPageKey)) {
                $perPage = request()->get($perPageKey);
            } else {
                $perPage = config('mr4recommendation.perPage', 5);
            }
        }
        $index = -1;
        $startIndex = $perPage * $page;
        $enđIndex = $startIndex + $perPage - 1;

        $matrixFile = (new static())->createMatrix($tableName, $product_id);
        $products = Items::fromFile($matrixFile, ['pointer' => '/matrix'])->getIterator();
        $result = [];
        $scores = [];
        $ids = [];
        while ($products->valid()) {
            $index++;
            if ($index < $startIndex) {
                $products->next();
                continue;
            }
            if ($index > $enđIndex) {
                $products->next();
                continue;
            }
            $product = $products->current();
            $result[] = $product;
            $scores[$product->id . ''] = $product->score;
            $ids[] = $product->id;
            $products->next();
        }
        if (count($ids) > 0) {
            $configs = config('mr4recommendation.mapping.tables.' . $tableName);
            $model = null;
            if (array_key_exists('model', $configs)) {
                $model = new $configs['model']();
                if (array_key_exists('model_with', $configs)) {
                    $model = $model->with($configs['model_with']);
                }
            } else {
                $model = DB::table($tableName);
            }
            $data = $model->whereIn($configs['map']['id'], $ids)
                ->orderByRaw('FIELD (' . $configs['map']['id'] . ', ' . implode(', ', $ids) . ') ASC')
                ->get()->toArray();
        } else {
            $data = [];
        }

        $total = $index + 1;
        $totalPage = intval(ceil($total / $perPage));
        $currentPage = $page + 1;
        $response = new stdClass();
        $response->current_page = $currentPage;
        $response->data = $data;
        $response->scores = $scores;
        $response->first_page_url = $routePath . '?' . http_build_query(array_merge($params, [$pagePrefix => 1]));
        $response->from = $startIndex + 1;
        $response->last_page = $totalPage;
        $response->last_page_url = $routePath . '?' . http_build_query(array_merge($params, [$pagePrefix => $totalPage]));

        $nextUrl = $currentPage >= $totalPage ? null : $routePath . '?' . http_build_query(array_merge($params, [$pagePrefix => $currentPage + 1]));
        $currentUrl = $routePath . '?' . http_build_query(array_merge($params, [$pagePrefix => $currentPage]));
        $prevUrl = $currentPage <= 1 ? null : $routePath . '?' . http_build_query(array_merge($params, [$pagePrefix => $page]));

        $links = [];
        $linkPrevious = new stdClass();
        $linkPrevious->url = $prevUrl;
        $linkPrevious->label = __('pagination.previous');
        $linkPrevious->active = $currentPage > 1;
        $links[] = $linkPrevious;

        $linkCurrent = new stdClass();
        $linkCurrent->url = $currentUrl;
        $linkCurrent->label = $currentPage;
        $linkCurrent->active = false;
        $links[] = $linkCurrent;

        $linkNext = new stdClass();
        $linkNext->url = $nextUrl;
        $linkNext->label = __('pagination.next');
        $linkNext->active = $currentPage < $totalPage;
        $links[] = $linkNext;

        $response->links = $links;
        $response->next_page_url = $nextUrl;
        $response->path = $routePath . '?'  . http_build_query($params);
        $response->per_page = $perPage;
        $response->prev_page_url = $prevUrl;
        $response->to = $enđIndex + 1;
        $response->total = $total;
        return $response;
    }
}
