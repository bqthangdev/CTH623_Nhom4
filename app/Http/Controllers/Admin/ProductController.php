<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreProductRequest;
use App\Http\Requests\Admin\UpdateProductRequest;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductImage;
use App\Repositories\ProductRepository;
use App\Services\ProductService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProductController extends Controller
{
    public function __construct(
        private readonly ProductRepository $productRepository,
        private readonly ProductService $productService,
    ) {}

    public function index(Request $request): View
    {
        $products = Product::with(['category', 'primaryImage'])
            ->when($request->q, fn ($q, $keyword) => $q->where('name', 'like', "%{$keyword}%"))
            ->when($request->category_id, fn ($q, $id) => $q->where('category_id', $id))
            ->when($request->status !== null, fn ($q) => $q->where('status', $request->status))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        $categories = Category::active()->orderBy('name')->get();

        return view('admin.products.index', compact('products', 'categories'));
    }

    public function create(): View
    {
        $categories = Category::active()->orderBy('name')->get();

        return view('admin.products.create', compact('categories'));
    }

    public function store(StoreProductRequest $request): RedirectResponse
    {
        $this->productService->create(
            $request->safe()->except('images', 'attributes'),
            $request->file('images', []),
        );

        return redirect()->route('admin.products.index')
            ->with('success', 'Tạo sản phẩm thành công!');
    }

    public function show(Product $product): View
    {
        $product->load(['images', 'category', 'attributes', 'reviews.user']);

        return view('admin.products.show', compact('product'));
    }

    public function edit(Product $product): View
    {
        $categories = Category::active()->orderBy('name')->get();
        $product->load(['images', 'attributes']);

        return view('admin.products.edit', compact('product', 'categories'));
    }

    public function update(UpdateProductRequest $request, Product $product): RedirectResponse
    {
        $this->productService->update(
            $product,
            $request->safe()->except('images', 'attributes'),
            $request->file('images', []),
        );

        return redirect()->route('admin.products.index')
            ->with('success', 'Cập nhật sản phẩm thành công!');
    }

    public function destroy(Product $product): RedirectResponse
    {
        $this->productService->delete($product);

        return redirect()->route('admin.products.index')
            ->with('success', 'Đã xóa sản phẩm.');
    }

    public function destroyImage(Product $product, ProductImage $image): RedirectResponse
    {
        abort_unless($image->product_id === $product->id, 403);

        \Illuminate\Support\Facades\Storage::disk('public')->delete($image->image_path);
        $image->delete();

        // Nếu xóa ảnh chính, đặt ảnh khác làm chính
        if ($image->is_primary) {
            $product->images()->first()?->update(['is_primary' => true]);
        }

        return back()->with('success', 'Đã xóa ảnh.');
    }
}
