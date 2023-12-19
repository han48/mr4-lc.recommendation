<?php

namespace Mr4Lc\Recommendation\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Mr4Lc\Recommendation\Similarity\ItemSimilarity;

class RecommendationController extends Controller
{
    public function recommendation()
    {
        $fields = request()->validate([
            'item_name' => ['required'],
            'item_id' => ['required'],
        ]);
        $result = ItemSimilarity::GetSimilarityItems($fields['item_name'], $fields['item_id']);
        return new JsonResponse($result, 200);
    }
}
