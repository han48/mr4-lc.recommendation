# mr4-lc.recommendation
Similarity hamming là một phép đo khoảng cách giữa hai chuỗi nhị phân, bằng cách đếm số lượng bit khác nhau giữa chúng. Ví dụ, similarity hamming giữa 1011 và 1101 là 2, vì có hai bit khác nhau ở vị trí thứ hai và thứ ba. Thuật toán để tính similarity hamming là rất đơn giản, chỉ cần so sánh từng bit của hai chuỗi nhị phân và tăng biến đếm nếu chúng khác nhau.
=> Sử dụng để tính toán similarity của features.

Similarity euclidean là một phép đo khoảng cách giữa hai điểm trong không gian nhiều chiều, bằng cách tính căn bậc hai của tổng bình phương các hiệu tọa độ của chúng. Ví dụ, similarity euclidean giữa hai điểm A(1, 2) và B(4, 6) là (4−1)2+(6−2)2​=25​=5. Thuật toán để tính similarity euclidean là rất đơn giản, chỉ cần áp dụng công thức sau:
d(A,B)=i=1∑n​(Ai​−Bi​)2​
Trong đó, d(A,B) là similarity euclidean giữa hai điểm A và B, n là số chiều của không gian, Ai​ và Bi​ là tọa độ của A và B trên chiều thứ i.
=> Sử dụng để tính toán similarity của price.

imilarity jaccard là một phép đo độ tương đồng giữa hai tập hợp, bằng cách chia số lượng phần tử chung cho số lượng phần tử khác nhau. Ví dụ, similarity jaccard giữa hai tập hợp A = {1, 2, 3} và B = {2, 3, 4} là 2/4 = 0.5, vì có hai phần tử chung là 2 và 3, và bốn phần tử khác nhau là 1, 2, 3, 4. Thuật toán để tính similarity jaccard là rất đơn giản, chỉ cần áp dụng công thức sau:
J(A,B)=∣A∪B∣∣A∩B∣​
Trong đó, J(A,B) là similarity jaccard giữa hai tập hợp A và B, ∣A∩B∣ là số lượng phần tử chung của A và B, ∣A∪B∣ là số lượng phần tử khác nhau của A và B.
=> Sử dụng để tính toán similarity của category.

## Installation
```bash
composer require mr4-lc/recommendation
php artisan vendor:publish --tag=mr4-lc-recommendation --force
```

## Configuration

```php
return [
    'featureWeight' => 1,
    'categoryWeight' => 1,
    'priceWeight' => 1,
    'priceHighRange' => 1000,

    'chunkSize' => 1000,
    'perPage' => 5,

    'output' => storage_path('app/private/recommendation/data'),

    'mapping' => [
        'tables' => [
            // Sample config
            'table_name' => [
                'data_version' => '0.0.1',
                'output' => 'table_name.json',
                // SQL WHERE condition
                'status' => [
                    'status' => 1,
                ],
                // SQL ORDER BY
                'order' => 'id',
                // Customize model response
                'model' => \App\Models\SomeModel::class,
                // Load relatíonhip
                'model_with' => ['tags'],
                // Customize similarity
                'class' => \App\Similarities\PostSimilarity::class,
                'map' => [
                    'id' => 'id',
                    'price' => 'price',
                    'categories' => ['categories', 'group'], // Array or string column name,
                    'features' => ['color', 'type'], // Array or string column name,
                ],
                'map_type' => [
                    'categories' => 'merge', // String data
                    'features' => 'object', // Object data
                ],
            ],
        ],
    ],
];
```

```php
namespace App\Similarities;

use Mr4Lc\Recommendation\Similarity\Similarity;

class PostSimilarity extends \Mr4Lc\Recommendation\Similarity\ItemSimilarity
{
    protected function calculateSimilarityScore($productA, $productB)
    {
        $productAFeatures = implode('', get_object_vars($productA->features));
        $productBFeatures = implode('', get_object_vars($productB->features));

        return array_sum([
            (Similarity::hamming($productAFeatures, $productBFeatures) * $this->featureWeight),
            (Similarity::jaccard($productA->categories, $productB->categories) * $this->categoryWeight)
        ]) / ($this->featureWeight + $this->categoryWeight);
    }

    public static function GetSimilarityPosts($product_id, $page = null, $perPage = null, $pagePrefix = 'page')
    {
        $products = static::GetSimilarityItems('posts', $product_id, $page, $perPage, $pagePrefix);
        return $products;
    }
}
```

## Usage

Create matrix
```bash
php artisan recommendation:export {tableName} {--chunkSize=}
```

Create similarity (take a long time, Be careful when you run it)
```bash
php artisan recommendation:create {tableName} {--id=}
```

```blade
<script>
    function buildView (response, container, ctrl, perPage, pagePrefix) {
        const items = document.createElement('div')
        items.className = 'items'
        response.data.forEach(element => {
            const div = document.createElement('div')
            div.className = 'item'
            if (element.name) {
                const name = document.createElement('div')
                name.className = 'name'
                name.innerHTML = element.name
                div.appendChild(name)
            }
            if (element.images) {
                const images = JSON.parse(element.images)
                if (images && images.length > 0) {
                    const img = document.createElement('img')
                    img.src = location.origin + '/public/' + images[0]
                    img.className = 'thumbnail'
                    div.appendChild(img)
                }
            }
            items.appendChild(div)
        });
        container.appendChild(items)
        const pagination = document.createElement('div')
        pagination.className = 'pagination'
        response.links.forEach(element => {
            const button = document.createElement('button')
            button.innerHTML = element.label
            button.className = element.active ? '' : 'inactive'
            pagination.appendChild(button)
            const urlParams = new URLSearchParams(element.url)
            const selectPage = urlParams.get(pagePrefix)
            button.onclick = () => {
                LoadRecommendation(ctrl, selectPage, perPage, pagePrefix)
            }
        })
        container.appendChild(pagination)
    }
</script>
<x-mr4-lc.recommendation itemName='wines' itemId='1' apiUrl='http://127.0.0.1:8000/api/recommendation' builder="buildView" />
```

```blade
<x-mr4-lc.recommendation itemName='wines' itemId='1' apiUrl='http://127.0.0.1:8000/api/recommendation' />
```

Controller
```php
$fields = request()->validate([
    'item_name' => ['required'],
    'item_id' => ['required'],
]);
$result = ItemSimilarity::GetSimilarityItems($fields['item_name'], $fields['item_id']);
return new JsonResponse($result, 200);
```
