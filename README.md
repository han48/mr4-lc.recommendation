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
