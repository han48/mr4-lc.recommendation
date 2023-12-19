<?php

namespace Mr4Lc\Recommendation\Similarity;

use Carbon\Carbon;
use Mr4Lc\JsonExporter\File;
use JsonMachine\Items;
use Mr4Lc\Recommendation\Console\Commands\RecommendationCreate;
use stdClass;

class ItemSimilarity
{
    use \Mr4Lc\Recommendation\Traits\ConsoleOutput;
    use \Mr4Lc\Recommendation\Traits\FilePath;

    const VERSION = '0.0.1';

    protected $fileName       = null;
    protected $matrixFileName = null;
    protected $pointer        = [];

    protected $products       = [];
    protected $featureWeight  = 1;
    protected $categoryWeight = 1;
    protected $priceWeight    = 1;
    protected $priceHighRange = 1000;

    public function __construct(string $fileName, array $pointer = [], $featureWeight = null, $categoryWeight = null, $priceWeight = null, $priceHighRange = null)
    {
        if (isset($featureWeight)) {
            $this->featureWeight = $featureWeight;
        } else {
            $this->featureWeight = config('mr4recommendation.featureWeight', 1);
        }
        if (isset($categoryWeight)) {
            $this->categoryWeight = $categoryWeight;
        } else {
            $this->categoryWeight = config('mr4recommendation.categoryWeight', 1);
        }
        if (isset($priceWeight)) {
            $this->priceWeight = $priceWeight;
        } else {
            $this->priceWeight = config('mr4recommendation.priceWeight', 1);
        }
        if (isset($priceHighRange)) {
            $this->priceHighRange = $priceHighRange;
        } else {
            $this->priceHighRange = config('mr4recommendation.priceHighRange', 1000);
        }
        $now = Carbon::now();
        $newFileName = substr($fileName, 0, strlen($this->fileName) - 5) . '_' . $now->timestamp . $this->generateRandomString() . '.json';
        copy($fileName, $newFileName);
        $this->fileName = $fileName;
        $this->matrixFileName = $newFileName;
        $this->pointer = $pointer;
        $this->priceHighRange = 0;
        $products = Items::fromFile($this->fileName, $this->pointer);
        foreach ($products as $product) {
            $this->priceHighRange = max($this->priceHighRange, $product->price);
        }
    }

    public function setFeatureWeight(float $weight): void
    {
        $this->featureWeight = $weight;
    }

    public function setPriceWeight(float $weight): void
    {
        $this->priceWeight = $weight;
    }

    public function setCategoryWeight(float $weight): void
    {
        $this->categoryWeight = $weight;
    }

    public function calculateSimilarityMatrix($product_id = null): string
    {
        $version = static::VERSION;
        $now = Carbon::now();
        $prefix = 'product_id_';

        $similarityMatrixFileNameTemplate = substr($this->fileName, 0, strlen($this->fileName) - 5) . '_similarity_matrix_:product_id.json';
        $result = $similarityMatrixFileNameTemplate;

        $products = Items::fromFile($this->fileName, $this->pointer);
        if (isset($product_id)) {
            $hasNew = false;
            $similarityMatrixFileName = __($similarityMatrixFileNameTemplate, ['product_id' => $product_id]);
            if (file_exists($similarityMatrixFileName)) {
                $oldMatrix = iterator_to_array(Items::fromFile($similarityMatrixFileName, ['pointer' => '/source']))[0];
                if (file_exists($oldMatrix)) {
                    $oldDataCreatedAt = iterator_to_array(Items::fromFile($oldMatrix, ['pointer' => '/created_at']))[0];
                    $currentDataCreatedAt = iterator_to_array(Items::fromFile($this->fileName, ['pointer' => '/created_at']))[0];
                    if ($oldDataCreatedAt > $currentDataCreatedAt) {
                        $hasNew = true;
                    }
                    $this->writeOutput("$oldDataCreatedAt > $currentDataCreatedAt: " . ($hasNew));
                }
            }
            if ($hasNew) {
                $product = null;
                foreach ($products->getIterator() as $item) {
                    if ($item->id === $product_id) {
                        $product = $item;
                        break;
                    }
                }
                if (isset($product)) {
                    $this->createSimilarityMatrix($product, $similarityMatrixFileName, $version, $prefix, $now);
                    $result = $similarityMatrixFileName;
                } else {
                    $result = null;
                }
            } else {
                $result = $similarityMatrixFileName;
            }
        } else {
            foreach ($products->getIterator() as $product) {
                $similarityMatrixFileName = __($similarityMatrixFileNameTemplate, ['product_id' => $product->id]);
                $this->createSimilarityMatrix($product, $similarityMatrixFileName, $version, $prefix, $now);
            }
        }
        return $result;
    }

    public function createSimilarityMatrix($product, $similarityMatrixFileName, $version, $prefix, $now)
    {
        $file = new File($similarityMatrixFileName);
        $itemCollection = $file->collection('matrix');
        $similarityScores = [];

        $products2 = Items::fromFile($this->fileName, $this->pointer);
        foreach ($products2->getIterator() as $_product) {
            $this->writeOutput("Calculate similarity score: " . $product->id . ' / ' . $_product->id);
            if ($product->id === $_product->id) {
                continue;
            }
            $similarityScores[$prefix . $_product->id] = $this->calculateSimilarityScore($product, $_product);
        }
        arsort($similarityScores);
        $objs = [];
        foreach ($similarityScores as $key => $value) {
            $obj = new stdClass();
            $obj->id = substr($key, strlen($prefix));
            $obj->score = $value;
            $objs[] = $obj;
        }
        $itemCollection->addItems($objs);
        $file->value('created_at', $now->toIso8601String());
        $file->value('version', $version);
        $file->value('source', $this->matrixFileName);
        $file->end();
    }

    protected function calculateSimilarityScore($productA, $productB)
    {
        $productAFeatures = implode('', get_object_vars($productA->features));
        $productBFeatures = implode('', get_object_vars($productB->features));

        return array_sum([
            (Similarity::hamming($productAFeatures, $productBFeatures) * $this->featureWeight),
            (Similarity::euclidean(
                Similarity::minMaxNorm([$productA->price], 0, $this->priceHighRange),
                Similarity::minMaxNorm([$productB->price], 0, $this->priceHighRange)
            ) * $this->priceWeight),
            (Similarity::jaccard($productA->categories, $productB->categories) * $this->categoryWeight)
        ]) / ($this->featureWeight + $this->priceWeight + $this->categoryWeight);
    }

    public static function GetSimilarityItems($tableName, $product_id, $page = null, $perPage = null, $pagePrefix = 'page')
    {
        $products = RecommendationCreate::GetSimilarityItems($tableName, $product_id, $page, $perPage, $pagePrefix);
        return $products;
    }
}
