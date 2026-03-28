# Phân tích chức năng Visual Search — SmartShop

---

## 1. Chi tiết từng hàm trong visual_search.py

> Đây là file Python cốt lõi của AI Service, chứa toàn bộ logic nhận diện hình ảnh.

---

### Khái niệm nền tảng cần biết trước

**CLIP là gì?**

CLIP (Contrastive Language–Image Pre-training) là mô hình AI do OpenAI phát triển năm 2021. Nó được huấn luyện trên hàng trăm triệu cặp ảnh–văn bản thu thập từ internet, học cách "hiểu" nội dung hình ảnh tương tự như con người.

Điều quan trọng là CLIP **không được huấn luyện lại** trong hệ thống này — ta chỉ dùng weights có sẵn của nó để trích xuất đặc trưng từ ảnh.

**Embedding là gì?**

Embedding là cách biểu diễn một vật thể phức tạp (ảnh, văn bản) dưới dạng một **dãy số** để máy tính có thể so sánh và tính toán. Trong hệ thống này, mỗi ảnh được chuyển thành một dãy **512 số thập phân** — gọi là vector 512 chiều.

Ví dụ minh họa đơn giản:
```
Ảnh chiếc áo đỏ (chụp góc trước):   [0.12, -0.45,  0.87, 0.03, ...]  ← 512 số
Ảnh chiếc áo đỏ (chụp góc sau):     [0.11, -0.43,  0.85, 0.04, ...]  ← rất gần nhau
Ảnh chiếc tủ lạnh:                   [0.91,  0.32, -0.11, 0.77, ...]  ← rất khác
```

Nguyên tắc then chốt: **hai ảnh càng giống nhau về nội dung → hai vector càng gần nhau trong không gian 512 chiều**.

---

### `load_clip_model()`

**Mục đích:** Nạp mô hình CLIP vào bộ nhớ RAM khi server khởi động.

**Hoạt động từng bước:**

1. Gọi `open_clip.create_model_and_transforms("ViT-B-32", pretrained="openai")`:
   - Tải và giải nén file weights (~350 MB) — bộ "não" của mô hình.
   - `ViT-B-32` là tên kiến trúc: **V**ision **T**ransformer, kích thước **B**ase, chia ảnh thành từng ô **32×32 pixels**.
   - Bước này chạy khoảng 10–30 giây tùy máy, chỉ chạy **một lần duy nhất** khi khởi động.

2. Đặt mô hình ở chế độ `eval()`:
   - Tắt Dropout (cơ chế tắt ngẫu nhiên một số neuron khi training để tránh overfit).
   - Cần thiết vì ta chỉ dùng để suy luận (inference), không tiếp tục training.

3. Tự phát hiện output dimension:
   - Tạo một ảnh giả 224×224, cho qua `encode_image()`, đọc `shape[1]` → được `512`.
   - Ghi vào biến toàn cục `_clip_embedding_dim = 512`.

4. Nếu có bất kỳ lỗi nào → ghi log, đặt `_clip_model = None`. Các request sau sẽ trả về lỗi có kiểm soát, server không bị crash.

---

### `load_embeddings()`

**Mục đích:** Đọc toàn bộ embedding của tất cả sản phẩm từ DB vào RAM để tìm kiếm tức thì.

**Hoạt động từng bước:**

1. Kết nối MySQL qua `_db_connect()`.
2. Chạy query: `SELECT product_id, embedding FROM product_embeddings`.
3. Mỗi dòng trả về: cột `embedding` là chuỗi JSON, ví dụ `"[0.12, -0.45, 0.87, ...]"`.
4. Parse chuỗi JSON → list số Python → chuyển thành `numpy array float32`.
5. Lưu vào dict: `_embeddings[product_id] = numpy_array`.

**Kết quả trong RAM:**
```python
_embeddings = {
    1:  array([0.12, -0.45,  0.87, ...]),  # sản phẩm id=1
    2:  array([0.91,  0.32, -0.11, ...]),  # sản phẩm id=2
    15: array([0.08, -0.39,  0.91, ...]),  # sản phẩm id=15
    ...
}
```

Từ thời điểm này, **mọi request tìm kiếm đều đọc từ dict trong RAM** — không truy vấn DB, tốc độ cực nhanh.

---

### `_extract_embedding(image_bytes)`

**Mục đích:** Nhận ảnh thô (bytes) từ người dùng upload → trả về vector embedding 512 chiều.

**Đây là hàm quan trọng nhất.** Mỗi lần người dùng tìm kiếm bằng ảnh, hàm này được gọi để "hiểu" nội dung ảnh đó.

**Bước 1 — Đọc và chuẩn hóa màu sắc:**
```
image_bytes → PIL.Image.open() → .convert("RGB")
```
- Ảnh JPEG thường đã là RGB. Ảnh PNG có thể có kênh alpha thứ 4 (RGBA = độ trong suốt) → chuyển về RGB để loại bỏ.
- Đảm bảo đầu vào luôn đồng nhất: 3 kênh màu Đỏ–Xanh lá–Xanh dương.

**Bước 2 — Tiền xử lý (preprocess):**
```
PIL Image (kích thước bất kỳ) → resize về 224×224 pixel → chuẩn hóa giá trị pixel
```
- CLIP được thiết kế để nhận ảnh 224×224 — phải resize trước.
- Chuẩn hóa pixel: trừ đi giá trị trung bình và chia cho độ lệch chuẩn theo chuẩn ImageNet. Giúp mô hình hoạt động ổn định với mọi loại ảnh.
- Kết quả: tensor `(1, 3, 224, 224)` — 1 ảnh, 3 kênh màu, 224×224 pixel.

**Bước 3 — Mô hình "nhìn" vào ảnh:**
```python
with torch.no_grad():
    features = _clip_model.encode_image(tensor)   # → tensor (1, 512)
```
- `torch.no_grad()`: tắt tính gradient — không cần thiết khi chỉ inference, giúp tiết kiệm ~50% RAM và tăng tốc ~30%.
- `encode_image()`: ảnh 224×224 được chia thành lưới 7×7 ô (**patch**), mỗi ô 32×32 pixel. Các patch này đi qua 12 lớp **Transformer** xử lý tuần tự, mỗi lớp học cách "chú ý" vào các vùng quan trọng của ảnh. Lớp cuối cùng tóm tắt toàn bộ thành vector 512 chiều.

**Bước 4 — L2 Normalization:**
```python
features = features / features.norm(dim=-1, keepdim=True)
```
- Tính độ dài (L2-norm) của vector: $\|\mathbf{v}\| = \sqrt{v_1^2 + v_2^2 + \cdots + v_{512}^2}$
- Chia từng phần tử cho độ dài → vector có độ dài đúng bằng 1 (gọi là **vector đơn vị**).
- **Tại sao?** Sau bước này, phép so sánh cosine giữa các vector trở thành phép nhân đơn giản (xem phần 3).

**Bước 5 — Trả về numpy array:**
```python
return features[0].numpy().astype(np.float32)   # shape (512,)
```

---

### `_active_embedding_method()`

Hàm tiện ích — trả về chuỗi mô tả model đang dùng, ví dụ `"CLIP ViT-B-32 (512-dim)"`. Dùng để ghi vào JSON response, giúp biết hệ thống đang chạy bằng model nào.

---

### `_db_connect()`

Tạo kết nối PyMySQL từ biến môi trường `DB_HOST`, `DB_PORT`, `DB_USERNAME`, `DB_PASSWORD`, `DB_DATABASE`. Mỗi lần gọi tạo một kết nối mới — không dùng connection pool vì chỉ gọi khi cần ghi.

---

### Endpoint `POST /api/visual-search`

**Đây là endpoint được gọi mỗi khi người dùng tìm kiếm bằng ảnh.**

**Bước 1 — Kiểm tra sẵn sàng:**
```python
if not _embeddings:
    return {"products": [], "embedding_method": ...}
```
Nếu dict rỗng (chưa có sản phẩm nào được tạo embedding) → trả về rỗng ngay, không xử lý tiếp.

**Bước 2 — Trích xuất embedding từ ảnh người dùng:**
```python
contents = await file.read()
query_vec = _extract_embedding(contents)   # vector 512 chiều
```

**Bước 3 — Lọc embedding hợp lệ:**
```python
expected_dim = len(query_vec)   # = 512
valid_ids = [pid for pid, emb in _embeddings.items() if len(emb) == expected_dim]
```
Đảm bảo chỉ so sánh với embedding cùng dimension. Nếu từng dùng model cũ (256-dim), embedding đó sẽ bị bỏ qua.

**Bước 4 — Tính similarity hàng loạt:**
```python
matrix = np.stack([_embeddings[pid] for pid in valid_ids])   # (n_products, 512)
scores = cosine_similarity([query_vec], matrix)[0]            # (n_products,)
```
Một phép nhân ma trận duy nhất → có ngay score của tất cả sản phẩm.

**Bước 5 — Sắp xếp và lọc ngưỡng:**
```python
top_indices = np.argsort(scores)[::-1][:10]   # top 10, thứ tự giảm dần
threshold = 0.60
results = [{"id": ..., "similarity_score": ...} for i in top_indices if scores[i] >= threshold]
```

**Bước 6 — Trả về JSON:**
```json
{
  "products": [
    {"id": 42, "similarity_score": 0.87},
    {"id": 15, "similarity_score": 0.81}
  ],
  "embedding_method": "CLIP ViT-B-32 (512-dim)"
}
```

---

### Endpoint `POST /api/embeddings/compute`

- Nhận file ảnh → gọi `_extract_embedding()` → trả về vector dưới dạng JSON list.
- **Không ghi DB.** Laravel gọi endpoint này lấy vector rồi tự tổng hợp (trung bình nhiều ảnh) trước khi lưu.

---

### Endpoint `POST /api/embeddings/store`

- Nhận `{ product_id: int, embedding: [float, ...] }`.
- Upsert vào MySQL: `INSERT INTO product_embeddings ... ON DUPLICATE KEY UPDATE embedding = VALUES(embedding)`.
- Cập nhật `_embeddings[product_id]` trong RAM ngay lập tức — sản phẩm mới có thể tìm thấy ngay, không cần restart service.

---

## 2. Luồng sinh embedding sản phẩm (offline)

> Đây là quá trình **chuẩn bị dữ liệu** — phải chạy trước khi hệ thống tìm kiếm hoạt động, hoặc mỗi khi thêm sản phẩm mới.

### Lệnh chạy

```bash
php artisan embeddings:generate
```

### Tại sao cần bước này?

Tìm kiếm bằng ảnh hoạt động bằng cách so sánh embedding của ảnh người dùng với embedding của tất cả sản phẩm. Vì vậy: **trước khi có thể tìm kiếm, phải tạo sẵn embedding cho mọi sản phẩm và lưu vào DB**.

Quá trình này chạy **offline** (không phải real-time) vì mỗi ảnh cần qua mô hình CLIP để tính embedding — tốn thời gian nếu có nhiều sản phẩm.

### Luồng xử lý (trong `GenerateProductEmbeddings.php`)

```
Với mỗi sản phẩm (load kèm ảnh bằng eager loading):
│
├── Với mỗi ảnh của sản phẩm:
│   ├── Đọc nội dung file từ storage/app/public/{đường_dẫn_ảnh}
│   ├── POST /api/embeddings/compute  (gửi bytes ảnh sang AI Service)
│   └── Nhận về vector 512 chiều → thêm vào $vectors[]
│
├── Gọi averageEmbeddings($vectors):
│   ├── Cộng tất cả vector lại theo từng chiều
│   ├── Chia cho số lượng ảnh → vector trung bình
│   └── L2 normalize → vector đơn vị
│
└── POST /api/embeddings/store { product_id, embedding: [...] }
    ├── Lưu vào bảng product_embeddings (MySQL)
    └── Cập nhật _embeddings[product_id] trong RAM ngay
```

### Hàm `averageEmbeddings(array $vectors)`

**Tại sao lấy trung bình nhiều ảnh thay vì một ảnh?**

Sản phẩm thường có nhiều ảnh: góc trước, góc sau, màu khác nhau. Nếu chỉ dùng một ảnh, kết quả tìm kiếm chỉ khớp tốt khi người dùng upload ảnh từ đúng góc đó. Lấy trung bình embedding tạo ra một "đại diện tổng hợp" của sản phẩm từ tất cả góc nhìn.

**Ví dụ minh họa** (dùng vector 3 chiều cho dễ hiểu, thực tế là 512 chiều):

```
Sản phẩm áo đỏ có 3 ảnh:
  ảnh 1 (góc trước):  [0.6,  0.8,  0.0]
  ảnh 2 (góc sau):    [0.7,  0.7,  0.1]
  ảnh 3 (màu khác):   [0.5,  0.9, -0.1]
```

**Bước 1 — Cộng element-wise (từng chiều một):**
```
chiều 0: 0.6 + 0.7 + 0.5 = 1.8
chiều 1: 0.8 + 0.7 + 0.9 = 2.4
chiều 2: 0.0 + 0.1 - 0.1 = 0.0
→ tổng: [1.8, 2.4, 0.0]
```

**Bước 2 — Chia cho số ảnh (3):**
```
trung bình: [0.60, 0.80, 0.00]
```

**Bước 3 — L2 Normalize:**
```
norm = sqrt(0.60² + 0.80² + 0.00²) = sqrt(0.36 + 0.64) = sqrt(1.00) = 1.00
kết quả: [0.60/1.00, 0.80/1.00, 0.00/1.00] = [0.60, 0.80, 0.00]
```

Vector kết quả có độ dài đúng bằng 1, sẵn sàng lưu vào DB.

---

## 3. Thuật toán Cosine Similarity

> Đây là thuật toán đo độ giống nhau giữa hai ảnh dựa trên embedding — trái tim của chức năng tìm kiếm.

### Ý tưởng trực quan

Hãy tưởng tượng mỗi embedding là một **mũi tên** xuất phát từ gốc tọa độ trong không gian 512 chiều. Hai mũi tên **chỉ cùng hướng** → hai ảnh giống nhau. Hai mũi tên **vuông góc** → hoàn toàn khác nhau.

Cosine Similarity đo **góc giữa hai mũi tên đó**:
- Góc = 0° (cùng hướng) → score = 1.0 (giống hoàn toàn)
- Góc = 90° (vuông góc) → score = 0.0 (khác hoàn toàn)
- Góc = 180° (ngược chiều) → score = -1.0 (nhưng hiếm gặp trong thực tế với CLIP)

### Tại sao không dùng khoảng cách thông thường?

Khoảng cách Euclidean phụ thuộc cả vào **hướng** lẫn **độ lớn** của vector. Hai ảnh cùng nội dung nhưng độ sáng khác nhau sẽ cho embedding cùng hướng nhưng khác độ lớn — Euclidean sẽ cho khoảng cách lớn (kết quả sai). Cosine chỉ đo hướng nên không bị ảnh hưởng bởi độ sáng.

### Công thức

$$\text{similarity}(\mathbf{q}, \mathbf{p}) = \frac{\mathbf{q} \cdot \mathbf{p}}{|\mathbf{q}| \cdot |\mathbf{p}|}$$

Trong đó:
- $\mathbf{q}$ = embedding ảnh người dùng upload
- $\mathbf{p}$ = embedding sản phẩm trong DB
- $\mathbf{q} \cdot \mathbf{p}$ = tổng tích từng cặp: $q_1 p_1 + q_2 p_2 + \cdots + q_{512} p_{512}$
- $|\mathbf{v}|$ = độ dài vector: $\sqrt{v_1^2 + v_2^2 + \cdots + v_{512}^2}$

### Đơn giản hóa nhờ L2 Normalization

Vì tất cả vector đã được L2-normalize (độ dài = 1):

$$|\mathbf{q}| = 1, \quad |\mathbf{p}| = 1$$

Mẫu số bằng 1, công thức rút gọn thành chỉ còn:

$$\text{similarity}(\mathbf{q}, \mathbf{p}) = \mathbf{q} \cdot \mathbf{p}$$

Đây là một phép nhân ma trận đơn giản, thực hiện rất nhanh bằng numpy/BLAS.

### Ví dụ tính tay (vector 2 chiều)

```
query_vec = [0.6, 0.8]   ← ảnh chiếc áo người dùng upload (đã normalize: 0.6²+0.8²=1)
product_A = [0.5, 0.9]   ← sản phẩm áo tương tự (đã normalize: 0.5²+0.9²≈1)
product_B = [0.9, 0.2]   ← sản phẩm hoàn toàn khác  (đã normalize: 0.9²+0.2²≈1)
```

```
sim(query, A) = 0.6×0.5 + 0.8×0.9 = 0.30 + 0.72 = 0.92   → xuất hiện trong kết quả (≥ 0.60)
sim(query, B) = 0.6×0.9 + 0.8×0.2 = 0.54 + 0.16 = 0.70   → xuất hiện trong kết quả (≥ 0.60)
```

### Ý nghĩa score trong thực tế

| Score | Mức độ tương đồng |
|---|---|
| 0.90 – 1.00 | Rất giống, gần như cùng sản phẩm hoặc ảnh góc khác nhau |
| 0.75 – 0.90 | Giống nhiều, cùng loại sản phẩm, màu tương tự |
| 0.60 – 0.75 | Tương đồng vừa, cùng nhóm danh mục |
| < 0.60 | Không đủ giống — bị lọc bỏ, không hiển thị |

### Trong code

```python
# Tạo ma trận: mỗi hàng là embedding một sản phẩm
matrix = np.stack([_embeddings[pid] for pid in valid_ids])   # shape (n, 512)

# Tính similarity của query với tất cả sản phẩm trong một lần
scores = cosine_similarity([query_vec], matrix)[0]            # shape (n,)

# Sắp xếp giảm dần, lấy 10 sản phẩm đầu tiên
top_indices = np.argsort(scores)[::-1][:10]

# Lọc theo ngưỡng
results = [
    {"id": valid_ids[i], "similarity_score": float(scores[i])}
    for i in top_indices
    if scores[i] >= threshold
]
```

`sklearn.cosine_similarity` thực hiện phép nhân `(1, 512) × (512, n)` bằng BLAS — song song hóa tự động, cực nhanh dù có hàng nghìn sản phẩm.

---

## 4. Các biến môi trường

| Biến | Mặc định | Mô tả |
|---|---|---|
| `VISUAL_SEARCH_THRESHOLD` | `0.60` | Ngưỡng cosine similarity tối thiểu để hiển thị kết quả |
| `CLIP_MODEL` | `ViT-B-32` | Kiến trúc CLIP đang dùng — đổi sang `ViT-L-14` để chính xác hơn nhưng chậm hơn |
| `AI_SERVICE_URL` | `http://localhost:8001` | Địa chỉ AI Service, cấu hình trong file `.env` của Laravel |
| `AI_SERVICE_TIMEOUT` | `30` | Thời gian chờ tối đa khi gọi AI Service (giây) |
| `DB_HOST` / `DB_PORT` | — | Kết nối MySQL cho AI Service (PyMySQL), lấy từ `.env` |
