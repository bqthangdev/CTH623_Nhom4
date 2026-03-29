# `features = features / features.norm(dim=-1, keepdim=True)` làm gì?

> Giải thích chi tiết cho người mới bắt đầu về AI và nhận diện hình ảnh.

---

## Một câu tóm tắt

Dòng này thực hiện **L2 Normalization** (chuẩn hóa L2) — chia vector 512 chiều cho độ dài của chính nó, để vector đó có độ dài đúng bằng **1**.

---

## Vấn đề cần giải quyết: "Độ lớn" làm nhiễu kết quả

Sau khi `encode_image()` trả về `features`, vector này có thể có độ dài bất kỳ — ví dụ:

```
Ảnh 1 (áo đỏ, chụp sáng):  features = [3.0,  4.0]   → độ dài = 5.0
Ảnh 2 (áo đỏ, chụp tối):   features = [0.6,  0.8]   → độ dài = 1.0
Ảnh 3 (tủ lạnh):            features = [4.8, -0.6]   → độ dài ≈ 4.8
```

*(Dùng 2 chiều cho dễ hình dung, thực tế là 512 chiều)*

Ảnh 1 và Ảnh 2 **cùng nội dung** (cùng chiếc áo đỏ), chỉ khác độ sáng. Nhưng vector của chúng có độ lớn rất khác nhau (5.0 vs 1.0).

Nếu tính khoảng cách giữa chúng mà không normalize:

```
Khoảng cách Euclidean (ước lượng):
  Ảnh1 vs Ảnh2 = sqrt((3.0-0.6)² + (4.0-0.8)²) = sqrt(5.76 + 10.24) = 4.0
  Ảnh1 vs Ảnh3 = sqrt((3.0-4.8)² + (4.0+0.6)²) = sqrt(3.24 + 21.16) ≈ 4.9
```

→ Khoảng cách Ảnh1–Ảnh2 (cùng áo đỏ) = 4.0 lại gần bằng Ảnh1–Ảnh3 (áo đỏ vs tủ lạnh) = 4.9. **Kết quả sai!**

**Nguyên nhân:** Khoảng cách bị ảnh hưởng bởi cả **hướng** (nội dung) lẫn **độ lớn** (cường độ sáng, contrast). Ta chỉ muốn so sánh **hướng** — tức nội dung.

---

## Giải pháp: Đưa tất cả về cùng độ dài = 1

Sau khi normalize:

```
Ảnh 1: [3.0, 4.0] / 5.0 = [0.6, 0.8]   → độ dài = 1.0
Ảnh 2: [0.6, 0.8] / 1.0 = [0.6, 0.8]   → độ dài = 1.0
Ảnh 3: [4.8, -0.6] / 4.8 ≈ [1.0, -0.125] → độ dài ≈ 1.0
```

Bây giờ Ảnh 1 và Ảnh 2 trở thành **vector giống hệt nhau** `[0.6, 0.8]` — vì chúng cùng nội dung, chỉ khác độ sáng. Khi tính similarity chỉ còn quan tâm đến **hướng** của vector.

---

## Chi tiết từng phần của dòng code

```python
features = features / features.norm(dim=-1, keepdim=True)
```

### `features.norm(dim=-1, keepdim=True)` — tính độ dài vector

**`norm`** (viết tắt của "normalization" nhưng ở đây nghĩa là tính L2-norm — chuẩn L2) = tính độ dài của vector theo công thức:

$$\|\mathbf{v}\| = \sqrt{v_1^2 + v_2^2 + v_3^2 + \cdots + v_{512}^2}$$

Ví dụ với vector `[3.0, 4.0]`:

$$\|[3.0, 4.0]\| = \sqrt{3.0^2 + 4.0^2} = \sqrt{9 + 16} = \sqrt{25} = 5.0$$

**`dim=-1`** — tính norm theo chiều cuối cùng của tensor:
- `features` có shape `(1, 512)` — 1 ảnh, 512 chiều
- `dim=-1` nghĩa là tính norm theo 512 chiều đó (không phải theo batch)
- Kết quả: shape `(1, 1)` — một con số duy nhất là độ dài

**`keepdim=True`** — giữ nguyên số chiều sau khi tính:
- Nếu không có `keepdim=True`: kết quả shape `(1,)` → phép chia sẽ bị lỗi broadcast
- Với `keepdim=True`: kết quả shape `(1, 1)` → phép chia hoạt động đúng

```python
# Ví dụ minh họa:
features = tensor([[3.0, 4.0]])            # shape (1, 2)
norm = features.norm(dim=-1, keepdim=True) # = tensor([[5.0]])  shape (1, 1)
```

### `features / ...` — chia từng phần tử cho độ dài

```python
features / norm
= [[3.0, 4.0]] / [[5.0]]
= [[3.0/5.0, 4.0/5.0]]
= [[0.6, 0.8]]
```

Mỗi trong số 512 phần tử đều được chia cho cùng một con số (độ dài). Kết quả là vector mới cùng **hướng** nhưng độ dài đúng bằng **1.0**.

### Kiểm tra kết quả:

$$\sqrt{0.6^2 + 0.8^2} = \sqrt{0.36 + 0.64} = \sqrt{1.00} = 1.0 \checkmark$$

---

## Tại sao điều này giúp tính cosine similarity nhanh hơn?

**Cosine Similarity** (độ tương đồng cosin — đo góc giữa hai vector) có công thức gốc:

$$\text{similarity}(\mathbf{q}, \mathbf{p}) = \frac{\mathbf{q} \cdot \mathbf{p}}{|\mathbf{q}| \cdot |\mathbf{p}|}$$

Khi cả hai vector đã normalize (độ dài = 1):

$$|\mathbf{q}| = 1, \quad |\mathbf{p}| = 1$$

$$\text{similarity}(\mathbf{q}, \mathbf{p}) = \frac{\mathbf{q} \cdot \mathbf{p}}{1 \times 1} = \mathbf{q} \cdot \mathbf{p}$$

Chỉ còn **một phép nhân tổng** (dot product — tích vô hướng) thay vì phải tính thêm hai căn bậc hai và một phép chia. Với 10.000 sản phẩm, tiết kiệm được 30.000 phép tính mỗi lần tìm kiếm.

---

## Hình dung trực quan trong không gian 2 chiều

```
        ↑ chiều 2
    1.0 │      ● Ảnh 2 (sau normalize) = [0.6, 0.8]
        │    ↗   ← cùng hướng với Ảnh 1
        │  ↗
        │↗  ● Ảnh 1 ban đầu = [3.0, 4.0]  (chưa normalize, nằm xa hơn)
   ─────┼──────────────────────────── → chiều 1
        │
        │                    ● Ảnh 3 = [1.0, -0.125] (sau normalize)
```

- Ảnh 1 và Ảnh 2 sau normalize **chồng khít lên nhau** → cosine similarity = 1.0
- Ảnh 3 nằm ở **hướng khác** → cosine similarity thấp

---

## Tóm tắt

| | Trước normalize | Sau normalize |
|---|---|---|
| Độ dài vector | Bất kỳ (ví dụ: 5.0, 1.0, 4.8) | Luôn = **1.0** |
| Ảnh sáng vs tối | Vector khác nhau hoàn toàn | **Vector giống nhau** |
| Tính similarity | Cần chia cho 2 độ dài | Chỉ cần dot product |
| Kết quả | Bị ảnh hưởng bởi độ sáng | Chỉ phụ thuộc nội dung |

**Dòng code một dòng này đảm bảo:** dù ảnh chụp sáng hay tối, màn hình điện thoại hay máy ảnh DSLR — nếu nội dung giống nhau thì vector giống nhau, và tìm kiếm sẽ cho kết quả đúng.
