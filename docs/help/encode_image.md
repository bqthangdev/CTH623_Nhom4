# `encode_image(tensor)` làm gì?

> Giải thích chi tiết cho người mới bắt đầu về AI và nhận diện hình ảnh.

---

## Một câu tóm tắt

`encode_image(tensor)` nhận vào một tấm ảnh đã được chuẩn hóa (tensor số) và trả về **một dãy 512 con số** tóm tắt toàn bộ nội dung của ảnh đó — gọi là **embedding** (véc-tơ đặc trưng).

---

## Bối cảnh: Tại sao cần "encode" ảnh?

Máy tính không "nhìn" được ảnh như con người. Nó chỉ thấy một mảng số:

```
Ảnh 224×224 pixel, 3 kênh màu
→ 224 × 224 × 3 = 150.528 con số (mỗi số từ 0.0 đến 1.0)
```

150.528 con số này **quá nhiều và quá thô** để so sánh trực tiếp. Hai ảnh cùng chiếc áo đỏ chụp ở góc khác nhau sẽ có 150.528 con số hoàn toàn khác nhau — dù nội dung giống nhau.

**Mục tiêu của `encode_image`:** nén 150.528 con số thô đó xuống còn **512 con số có ý nghĩa** — sao cho ảnh giống nhau về nội dung cho ra 512 số gần giống nhau.

---

## Kiến trúc bên trong: Vision Transformer (ViT — Bộ biến đổi thị giác)

`encode_image` không phải một hàm đơn giản. Bên trong nó là một mạng nơ-ron (neural network — mạng lưới thần kinh nhân tạo) gồm **hàng chục triệu phép tính** được tổ chức thành nhiều lớp (layer — tầng xử lý).

### Bước 1 — Chia ảnh thành các ô nhỏ (Patch — mảnh)

```
Ảnh 224×224 pixel
      ↓  chia thành lưới 7×7
49 ô vuông, mỗi ô 32×32 pixel
```

Hình dung như chia tờ giấy thành 49 ô vuông bằng nhau:

```
┌───┬───┬───┬───┬───┬───┬───┐
│ 1 │ 2 │ 3 │ 4 │ 5 │ 6 │ 7 │
├───┼───┼───┼───┼───┼───┼───┤
│ 8 │ 9 │10 │11 │12 │13 │14 │
├───┼───┼───┼───┼───┼───┼───┤
│...│...│...│...│...│...│...│
└───┴───┴───┴───┴───┴───┴───┘
  Ô 1: góc trên trái (32×32 pixel)
  Ô 25: giữa ảnh
  Ô 49: góc dưới phải
```

**Tại sao chia ô?** Transformer (kiến trúc được dùng trong CLIP) không xử lý toàn bộ ảnh một lúc mà xử lý từng phần nhỏ và học cách chúng liên quan đến nhau.

---

### Bước 2 — Mỗi ô → một vector số (Patch Embedding — véc-tơ mảnh)

Mỗi ô 32×32 pixel (= 32×32×3 = 3.072 số) được chiếu qua một phép biến đổi tuyến tính (linear projection — phép nhân ma trận) để nén thành **một vector 768 số**:

```
Ô 1 (3.072 số)  →  vector_1 (768 số)
Ô 2 (3.072 số)  →  vector_2 (768 số)
...
Ô 49 (3.072 số) →  vector_49 (768 số)
```

Ngoài ra, thêm một vector đặc biệt `[CLS]` (viết tắt của "classification token" — token phân loại) ở đầu danh sách, đóng vai trò **"trưởng nhóm"** tổng hợp thông tin sau này:

```
[CLS], vector_1, vector_2, ..., vector_49   →  50 vectors, mỗi vector 768 số
```

---

### Bước 3 — Thêm thông tin vị trí (Positional Encoding — mã hóa vị trí)

Transformer không tự biết ô số 1 ở góc trên trái hay ô số 49 ở góc dưới phải — nó chỉ thấy một danh sách 50 vectors. Để bổ sung thông tin "ô này đứng ở đâu trong ảnh", cộng thêm **position embedding** (véc-tơ vị trí) vào mỗi vector:

```
vector_1 = patch_embedding_1 + position_embedding_1
vector_2 = patch_embedding_2 + position_embedding_2
...
```

Sau bước này, mỗi vector vừa chứa thông tin **nội dung ô** vừa chứa thông tin **vị trí của ô** trong ảnh.

---

### Bước 4 — Qua 12 lớp Transformer (Multi-Head Self-Attention — Chú ý đa đầu)

Đây là **trái tim** của toàn bộ mô hình. 50 vectors đi qua **12 lớp Transformer** xử lý tuần tự.

**Mỗi lớp Transformer làm hai việc:**

#### Việc 1 — Self-Attention (Tự chú ý): Các ô "nhìn" lẫn nhau

Mỗi ô được phép "hỏi thăm" tất cả ô còn lại: *"Ô nào trong ảnh liên quan đến tôi?"*

```
Ví dụ ảnh chiếc áo:
  Ô chứa cổ áo hỏi: "Ai liên quan đến tôi?"
  → Ô chứa thân áo: "Tôi!" (score cao)
  → Ô chứa nền trắng: "Không phải tôi" (score thấp)
  → Ô chứa tay áo: "Tôi một ít" (score vừa)
```

Kết quả: mỗi ô cập nhật vector của mình bằng cách tổng hợp thông tin từ các ô liên quan, **có trọng số** theo mức độ liên quan.

**"Multi-Head" (đa đầu)** nghĩa là bước này được thực hiện **8 lần song song**, mỗi lần chú ý đến khía cạnh khác nhau (màu sắc, hình dạng, kết cấu...), rồi ghép lại.

#### Việc 2 — Feed-Forward (Truyền thẳng): Xử lý thông tin đã tổng hợp

Mỗi vector sau bước attention đi qua một mạng nhỏ 2 lớp để học cách biến đổi thông tin:

```
vector (768 số) → lớp 1 (3.072 số) → ReLU (hàm kích hoạt) → lớp 2 (768 số)
```

**Sau 12 lớp lặp lại:** mỗi vector chứa thông tin **toàn cục** của ảnh — không chỉ ô đó mà còn tổng hợp từ tất cả ô liên quan, qua 12 vòng "họp nhóm".

---

### Bước 5 — Lấy vector [CLS] làm đại diện

Sau 12 lớp Transformer, lấy vector của token `[CLS]` — "trưởng nhóm" đã tổng hợp thông tin từ tất cả 49 ô:

```
[CLS_final, v1_final, v2_final, ..., v49_final]
     ↑
  Lấy cái này — nó đại diện cho toàn bộ ảnh
  shape: (768,)
```

---

### Bước 6 — Chiếu xuống 512 chiều (Projection — chiếu)

Vector 768 chiều được nhân với một ma trận học được (768×512) để nén xuống còn 512 chiều — đây là output dimension của ViT-B/32:

```
vector (768,)  ×  W (768×512)  →  features (512,)
```

---

## Toàn bộ luồng trong một sơ đồ

```
tensor (1, 3, 224, 224)
        ↓
  Chia thành 49 ô 32×32
        ↓
  Patch Embedding: mỗi ô → vector 768 số
        ↓
  Thêm [CLS] token + Position Encoding
        ↓
  50 vectors (768 số mỗi vector)
        ↓
  Lớp Transformer 1:  Self-Attention → Feed-Forward
        ↓
  Lớp Transformer 2:  Self-Attention → Feed-Forward
        ↓
        ...  (12 lớp tổng cộng)
        ↓
  Lớp Transformer 12: Self-Attention → Feed-Forward
        ↓
  Lấy vector [CLS] (768 số)
        ↓
  Linear Projection: 768 → 512
        ↓
features (1, 512)  ← OUTPUT của encode_image()
```

---

## Tại sao 512 con số này "có ý nghĩa"?

CLIP được huấn luyện bằng cách so sánh **hàng trăm triệu cặp ảnh–chú thích văn bản**. Với mỗi cặp, mô hình học để:
- Embedding của ảnh "chiếc áo đỏ" → **gần** với embedding của văn bản "a red shirt"
- Embedding của ảnh "chiếc áo đỏ" → **xa** với embedding của văn bản "a blue car"

Sau hàng tỷ lần điều chỉnh trọng số, 512 con số đầu ra tự nhiên trở thành **tọa độ ngữ nghĩa** (semantic coordinates — tọa độ ý nghĩa) — ảnh cùng loại nằm gần nhau trong không gian 512 chiều này.

---

## Tóm tắt những gì diễn ra

| Bước | Input | Output | Ý nghĩa |
|---|---|---|---|
| Chia patch | Ảnh 224×224 | 49 ô 32×32 | Chia nhỏ để xử lý từng phần |
| Patch Embedding | 49 ô pixel | 49 vectors 768-dim | Chuyển pixel thô → biểu diễn số học |
| + CLS + Position | 49 vectors | 50 vectors 768-dim | Thêm thông tin vị trí |
| 12× Transformer | 50 vectors | 50 vectors 768-dim | Các ô "học" từ nhau 12 lần |
| Lấy [CLS] | 50 vectors | 1 vector 768-dim | Tổng hợp toàn ảnh |
| Projection | 768-dim | 512-dim | Nén về output dimension |

**Kết quả cuối:** `features` shape `(1, 512)` — 512 con số tóm tắt **ngữ nghĩa** của toàn bộ ảnh, sẵn sàng để so sánh với các sản phẩm khác bằng cosine similarity.
