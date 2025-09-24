<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use App\Models\Size;
use App\Models\Color;
use App\Models\Category;
use App\Models\Product;
use App\Models\PromotionItem;
use App\Models\Supplier;
use App\Models\StockTransaction;
use App\Traits\GeneratesUniqueCode;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Carbon\Carbon;
use Illuminate\Validation\Rule;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;




class ProductController extends Controller
{
    use GeneratesUniqueCode;

    public function test(Request $request)
    {
        $allcategories = Category::with('parent')->get()->map(function ($category) {
            $category->hierarchy_string = $category->hierarchy_string; // Access it
            return $category;
        });
        return Inertia::render('Products/index2', [
            'categories' => $allcategories
        ]);
    }



public function fetchProducts(Request $request)
{
    $query = $request->input('search');
    $sortOrder = $request->input('sort');
    $selectedColor = $request->input('color');
    $selectedSize = $request->input('size');
    $stockStatus = $request->input('stockStatus');
    $selectedCategory = $request->input('selectedCategory');

    $productsQuery = Product::with('category', 'color', 'size', 'supplier')
        ->whereNotNull('products.name')
        ->when($query, function ($qb) use ($query) {
            $qb->where(function ($sub) use ($query) {
                $sub->where('products.name', 'like', "%{$query}%")
                    ->orWhere('products.code', 'like', "%{$query}%");
            });
        })
        ->when($selectedColor, function ($qb) use ($selectedColor) {
            $qb->whereHas('color', function ($cq) use ($selectedColor) {
                $cq->where('name', $selectedColor);
            });
        })
        ->when($selectedSize, function ($qb) use ($selectedSize) {
            $qb->whereHas('size', function ($sq) use ($selectedSize) {
                $sq->where('name', $selectedSize);
            });
        })
        ->when($stockStatus, function ($qb) use ($stockStatus) {
            if ($stockStatus === 'in') {
                $qb->where('products.stock_quantity', '>', 0);
            } elseif ($stockStatus === 'out') {
                $qb->where('products.stock_quantity', '<=', 0);
            }
        })
        ->when($selectedCategory, function ($qb) use ($selectedCategory) {
            $qb->where('products.category_id', $selectedCategory);
        });

    // Always push out-of-stock to the end
    $productsQuery->orderByRaw("CASE WHEN products.stock_quantity > 0 THEN 0 ELSE 1 END");

    // Secondary sort: price or FIFO by created_at
    if (in_array($sortOrder, ['asc', 'desc'])) {
        $productsQuery->orderBy('products.selling_price', $sortOrder);
    } else {
        // FIFO: oldest first within each stock group
        $productsQuery->orderBy('products.created_at', 'asc');
    }

    $products = $productsQuery->paginate(8);

    return response()->json([
        'products' => $products,
    ]);
}








    /**
     * Display a listing of the resource.
     */


    public function index(Request $request)
    {
        $query = $request->input('search');
        $sortOrder = $request->input('sort');
        $selectedColor = $request->input('color');
        $selectedSize = $request->input('size');
        $stockStatus = $request->input('stockStatus');
        $selectedCategory = $request->input('selectedCategory');

        // Base query
        $productsQuery = Product::with(['category', 'color', 'size', 'supplier'])
            ->whereNotNull('name')
            ->when($query, fn($q) => $q->where(fn($sub) =>
                $sub->where('name', 'like', "%{$query}%")
                    ->orWhere('code', 'like', "%{$query}%")))
            ->when($selectedColor, fn($q) =>
                $q->whereHas('color', fn($c) => $c->where('name', $selectedColor)))
            ->when($selectedSize, fn($q) =>
                $q->whereHas('size', fn($s) => $s->where('name', $selectedSize)))
            ->when($selectedCategory, fn($q) =>
                $q->where('category_id', $selectedCategory))
            ->when($stockStatus, function ($q) use ($stockStatus) {
                if ($stockStatus === 'in') {
                    $q->where('stock_quantity', '>', 0);
                } elseif ($stockStatus === 'out') {
                    $q->where('stock_quantity', '<=', 0);
                }
            });

        // Apply price sorting if specified
        if ($sortOrder === 'asc' || $sortOrder === 'desc') {
            $productsQuery->orderBy('selling_price', $sortOrder);
        } else {
            $productsQuery->orderBy('created_at', 'desc');
        }

        // Final paginated result
        $products = $productsQuery->paginate(8)->withQueryString();

        // Total filtered products
        $totalProducts = $products->total();

        // Other dropdown / alert data
        $allcategories = Category::with('parent')->get()->map(function ($category) {
            $category->hierarchy_string = $category->hierarchy_string;
            return $category;
        });

        $preOrderProducts = Product::with(['category', 'supplier', 'color', 'size'])
            ->whereColumn('total_quantity', '<=', 'preorder_level_qty')
            ->get();

        $preOrderAlertCount = $preOrderProducts->count();

        $expiryProducts = Product::with(['category', 'supplier'])
            ->whereNotNull('expire_date')
            ->get()
            ->map(function ($product) {
                $product->days_left = Carbon::now()->diffInDays(Carbon::parse($product->expire_date), false);
                $product->within_margin = $product->days_left <= $product->expiry_date_margin;
                return $product;
            })
            ->filter(fn($p) => $p->within_margin)
            ->values();

        $expiryAlertCount = $expiryProducts->count();

        return Inertia::render('Products/Index', [
            'products' => $products,
            'rawProducts' => $products->items(), // send current page items for barcode modal
            'allcategories' => $allcategories,
            'colors' => Color::latest()->get(),
            'sizes' => Size::latest()->get(),
            'suppliers' => Supplier::latest()->get(),
            'totalProducts' => $totalProducts,
            'search' => $query,
            'sort' => $sortOrder,
            'color' => $selectedColor,
            'size' => $selectedSize,
            'stockStatus' => $stockStatus,
            'preOrderAlertCount' => $preOrderAlertCount,
            'preOrderProducts' => $preOrderProducts,
            'selectedCategory' => $selectedCategory,
            'expiryProducts' => $expiryProducts,
            'expiryAlertCount' => $expiryAlertCount,
        ]);
    }



    /**
     * Show the form for creating a new resource.
     */
    // public function create()
    // {
    //     $categories = Category::all();
    //     $products = Product::all();
    //     $suppliers = Supplier::all();
    //     $colors = Color::all();
    //     $sizes = Size::all();



    //     return Inertia::render('Products/Create', [
    //         'products' => $products,
    //         'categories' => $categories,
    //         'suppliers' => $suppliers,
    //         'colors' => $colors,
    //         'sizes' => $sizes,
    //     ]);
    // }

    /**
     * Store a newly created resource in storage.
     */




    public function store(Request $request)
    {


        if (!Gate::allows('hasRole', ['Admin'])) {
            abort(403, 'Unauthorized');
        }

        $validated = $request->validate([
            'category_id' => 'nullable|exists:categories,id',
            'name' => 'required|string|max:255',
            'code' => 'nullable|max:50',
            // 'code' => [
            //     'string',
            //     'max:50',
            //     Rule::unique('products')->whereNull('deleted_at'),
            // ],
            'size_id' => 'nullable|exists:sizes,id',
            'color_id' => 'nullable|exists:colors,id',
            'cost_price' => 'nullable|numeric|min:0',
            'selling_price' => [
                'nullable',
                'numeric',
                'min:0',
                function ($attribute, $value, $fail) use ($request) {
                    if ($value < $request->input('cost_price')) {
                        $fail('The selling price must be greater than or equal to the cost price.');
                    }
                },
            ],
            'discounted_price' => 'nullable|numeric|min:0',
            'discount' => 'nullable|numeric|min:0|max:100',
            'stock_quantity' => 'nullable|integer|min:0',
            'preorder_level_qty' => 'nullable|integer|min:0',

            'supplier_id' => 'nullable|exists:suppliers,id',
            'barcode' => 'nullable|string|unique:products',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'expire_date' => 'nullable|date',
            'expiry_date_margin' => 'nullable|integer|min:0',
            'batch_no' => 'nullable|max:50',
            'purchase_date' => 'nullable|date',


            'whole_price' => [
                'nullable',
                'numeric',
                'min:0',
                function ($attribute, $value, $fail) use ($request) {
                    if ($value < $request->input('cost_price')) {
                        $fail('The selling price must be greater than or equal to the cost price.');
                    }
                },
            ],
            'wholesale_discount' => 'nullable|numeric|min:0|max:100',
            'final_whole_price' => 'nullable|numeric|min:0',
            'certificate' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:2048',

        ]);



        try {
            // Handle image upload
            if ($request->hasFile('image')) {
                $fileExtension = $request->file('image')->getClientOriginalExtension();
                $fileName = 'product_' . date("YmdHis") . '.' . $fileExtension;
                $path = $request->file('image')->storeAs('products', $fileName, 'public');
                $validated['image'] = 'storage/' . $path;
            }

            if (empty($validated['barcode'])) {
                $validated['barcode'] = $this->generateUniqueCode();
            }



            if ($request->hasFile('certificate')) {
                $fileExtension = $request->file('certificate')->getClientOriginalExtension();
                $fileName = 'certificate_' . date("YmdHis") . '.' . $fileExtension;
                $path = $request->file('certificate')->storeAs('certificates', $fileName, 'public'); // folder: certificates (plural is better)
                $validated['certificate_path'] = 'storage/' . $path;
            }



            $validated['total_quantity'] = $validated['stock_quantity'] ?? 0;
            // Create the product
            $product = Product::create($validated);
            // $product->update(['code' => 'PROD-' . $product->id]);

            // Add stock transaction if stock quantity is provided
            $stockQuantity = $validated['stock_quantity'] ?? 0; // Default to 0 if not provided
            if ($stockQuantity > 0) {
                StockTransaction::create([
                    'product_id' => $product->id,
                    'transaction_type' => 'Added',
                    'quantity' => $stockQuantity,
                    'transaction_date' => now(),
                    'supplier_id' => $validated['supplier_id'] ?? null,
                ]);
            }

            // Redirect with success message
            return redirect()->route('products.index')->banner('Product created successfully');
        } catch (\Exception $e) {

            \Log::error('Error creating product: ' . $e->getMessage());

            return redirect()->back()->with('error', 'An error occurred while creating the product. Please try again.');
        }
    }
















    public function productVariantStore(Request $request)
    {
        if (!Gate::allows('hasRole', ['Admin'])) {
            abort(403, 'Unauthorized');
        }

        $validated = $request->validate([
            'category_id' => 'nullable|exists:categories,id',
            'name' => 'required|string|max:255',
            'code' => [
                'nullable',
                'string',
                'max:50',
                Rule::unique('products')->whereNull('deleted_at'),
            ],
            'barcode' => [
                'nullable',
                'string',
                Rule::unique('products')->whereNull('deleted_at'),
            ],
            'size_id' => 'nullable|exists:sizes,id',
            'color_id' => 'nullable|exists:colors,id',
            'cost_price' => 'nullable|numeric|min:0',
            'selling_price' => [
                'nullable',
                'numeric',
                'min:0',
                function ($attribute, $value, $fail) use ($request) {
                    if ($value < ($request->input('cost_price') ?? 0)) {
                        $fail('The selling price must be greater than or equal to the cost price.');
                    }
                },
            ],
            'discounted_price' => 'nullable|numeric|min:0',
            'stock_quantity' => 'nullable|integer|min:0',
            'discount' => 'nullable|numeric|min:0|max:100',
            'supplier_id' => 'nullable|exists:suppliers,id',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg,webp|max:2048',
            'expire_date' => 'nullable|date',
            'expiry_date_margin' => 'nullable|integer|min:0',
            'preorder_level_qty' => 'nullable|integer|min:0',
            'purchase_date' => 'nullable|date',
            'batch_no' => 'nullable|string|max:50',
            'whole_price' => [
                'nullable',
                'numeric',
                'min:0',
                function ($attribute, $value, $fail) use ($request) {
                    if ($value < $request->input('cost_price')) {
                        $fail('The selling price must be greater than or equal to the cost price.');
                    }
                },
            ],
            'wholesale_discount' => 'nullable|numeric|min:0|max:100',
            'final_whole_price' => 'nullable|numeric|min:0',
            'certificate' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:2048',
        ]);

        try {
            // Handle image upload
            if ($request->hasFile('image')) {
                $fileExtension = $request->file('image')->getClientOriginalExtension();
                $fileName = 'product_' . date("YmdHis") . '.' . $fileExtension;
                $path = $request->file('image')->storeAs('products', $fileName, 'public');
                $validated['image'] = 'storage/' . $path;
            }


            // Generate barcode if not provided
            if (empty($validated['barcode'])) {
                $validated['barcode'] = $this->generateUniqueBarcode();
            }

            if ($request->hasFile('certificate')) {
                $certificatePath = $request->file('certificate')->store('certificates', 'public');
                $product->certificate_path = $certificatePath;
            }


            // Create product
            $product = Product::create($validated);

            // Add stock transaction if stock quantity exists
            $stockQuantity = $validated['stock_quantity'] ?? 0;
            if ($stockQuantity > 0) {
                StockTransaction::create([
                    'product_id' => $product->id,
                    'transaction_type' => 'Added',
                    'quantity' => $stockQuantity,
                    'transaction_date' => now(),
                    'supplier_id' => $validated['supplier_id'] ?? null,
                ]);
            }

            return redirect()->route('products.index')->banner('Product created successfully');
        } catch (\Exception $e) {
            Log::error('Error creating product: ' . $e->getMessage());
            return redirect()->back()->with('error', 'An error occurred while creating the product. Please try again.');
        }
    }

    // Helper to generate unique barcode
    private function generateUniqueBarcode($length = 7)
    {
        do {
            $barcode = strtoupper(Str::random($length));
        } while (Product::where('barcode', $barcode)->exists());

        return $barcode;
    }


    /**
     * Display the specified resource.
     */
    public function show(Product $product)
    {
        if (!Gate::allows('hasRole', ['Admin'])) {
            abort(403, 'Unauthorized');
        }
        // $categories = Category::all();
        // $sizes = Size::all();
        // $suppliers = Supplier::all();
        // $colors = Color::all();
        $categories = Category::orderBy('created_at', 'desc')->get();
        $colors = Color::orderBy('created_at', 'desc')->get();
        $sizes = Size::orderBy('created_at', 'desc')->get();
        $suppliers = Supplier::orderBy('created_at', 'desc')->get();

        $product->load('category', 'color', 'size', 'suppliers');

        return Inertia::render('Products/Show', [

            'categories' => $categories,
            'product' => $product,
            'suppliers' => $suppliers,
            'colors' => $colors,
            'sizes' => $sizes,
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Product $product)
    {
        $categories = Category::orderBy('created_at', 'desc')->get();
        $colors = Color::orderBy('created_at', 'desc')->get();
        $sizes = Size::orderBy('created_at', 'desc')->get();
        $suppliers = Supplier::orderBy('created_at', 'desc')->get();

        return inertia('Products/Edit', [
            'product' => $product,
            'categories' => $categories,
            'suppliers' => $suppliers,
            'colors' => $colors,
            'sizes' => $sizes,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */



    public function update(Request $request, Product $product)
{
    if (!Gate::allows('hasRole', ['Admin'])) {
        abort(403, 'Unauthorized');
    }

    try {
        // Build rules, validate files only if newly uploaded
        $rules = [
            'category_id'         => 'nullable|exists:categories,id',
            'supplier_id'         => 'nullable|exists:suppliers,id',
            'name'                => 'required|string|max:255',
            'size_id'             => 'nullable|exists:sizes,id',
            'color_id'            => 'nullable|exists:colors,id',
            'cost_price'          => 'required|numeric|min:0',
            'selling_price'       => 'required|numeric|min:0',
            'discounted_price'    => 'nullable|numeric|min:0',
            'discount'            => 'nullable|numeric|min:0|max:100',
            'stock_quantity'      => 'required|integer|min:0',
            'expire_date'         => 'nullable|date',
            'expiry_date_margin'  => 'nullable|integer|min:0',
            'preorder_level_qty'  => 'nullable|integer|min:0',
            'batch_no'            => 'nullable|string|max:50',
            'purchase_date'       => 'nullable|date',
            'whole_price'         => 'nullable|numeric|min:0',
            'final_whole_price'   => 'nullable|numeric|min:0',
            'wholesale_discount'  => 'nullable|numeric|min:0|max:100',
            'code'                => 'nullable|max:50',
        ];

        // Only enforce "image" rule when a new image is uploaded
        $rules['image'] = $request->hasFile('image')
            ? 'image|mimes:jpg,jpeg,png,bmp,webp|max:2048'
            : 'nullable|string';

        // Only enforce "file" rule when a new certificate is uploaded
        $rules['certificate'] = $request->hasFile('certificate')
            ? 'file|mimes:pdf,jpg,jpeg,png|max:2048'
            : 'nullable|string';

        $validated = $request->validate($rules);

        DB::beginTransaction();

        /*-------------------------------------------------
         | IMAGE UPLOAD (public disk) + delete old file
         *------------------------------------------------*/
        if ($request->hasFile('image')) {
            // delete old if exists
            $oldImagePath = $product->image ? str_replace('storage/', '', $product->image) : null;
            if ($oldImagePath && Storage::disk('public')->exists($oldImagePath)) {
                Storage::disk('public')->delete($oldImagePath);
            }

            $fileName = 'product_' . now()->format('YmdHis') . '.' . $request->file('image')->getClientOriginalExtension();
            $path = $request->file('image')->storeAs('products', $fileName, 'public');
            // save as "storage/..." so it's web accessible via symlink
            $validated['image'] = 'storage/' . $path;
        } else {
            // keep the previous image path
            $validated['image'] = $product->image;
        }

        /*-------------------------------------------------
         | CERTIFICATE UPLOAD (public disk) + delete old
         *------------------------------------------------*/
        if ($request->hasFile('certificate')) {
            $oldCertPath = $product->certificate_path ?: null;
            if ($oldCertPath && Storage::disk('public')->exists($oldCertPath)) {
                Storage::disk('public')->delete($oldCertPath);
            }

            $certificatePath = $request->file('certificate')->store('certificates', 'public');
            $validated['certificate_path'] = $certificatePath;
        } else {
            $validated['certificate_path'] = $product->certificate_path;
        }

        /*-------------------------------------------------
         | STOCK & TOTAL QUANTITY
         *------------------------------------------------*/
        $newQuantity = $validated['stock_quantity'] ?? $product->stock_quantity;
        $stockChange = (int)$newQuantity - (int)$product->stock_quantity;

        // keep total_quantity in sync with the new stock count (if that's your logic)
        $validated['total_quantity'] = $newQuantity;

        /*-------------------------------------------------
         | UPDATE PRODUCT
         *------------------------------------------------*/
        $product->update($validated);

        /*-------------------------------------------------
         | STOCK TRANSACTION (only if stock changed)
         *------------------------------------------------*/
        if ($stockChange !== 0) {
            $transactionType = $stockChange > 0 ? 'Added' : 'Deducted';

            StockTransaction::create([
                'product_id'       => $product->id,
                'transaction_type' => $transactionType,
                'quantity'         => abs($stockChange),
                'transaction_date' => now(),
                'supplier_id'      => $validated['supplier_id'] ?? null,
            ]);
        }

        DB::commit();

        return redirect()
            ->route('products.index')
            ->with('banner', 'Product updated successfully');

    } catch (\Throwable $e) {
        DB::rollBack();

        Log::error('Product update failed', [
            'product_id' => $product->id ?? null,
            'error'      => $e->getMessage(),
            // comment the trace in production if too noisy
            'trace'      => $e->getTraceAsString(),
        ]);

        return back()->withErrors([
            'error' => 'Product update failed. Please try again.',
        ])->withInput();
    }
}












    public function destroy(Product $product)
    {
        if (!Gate::allows('hasRole', ['Admin'])) {
            abort(403, 'Unauthorized');
        }

        // Prepare to delete the image
        $imagePath = str_replace('storage/', '', $product->image);
        $imageUsageCount = Product::where('image', $product->image)
            ->where('id', '!=', $product->id)
            ->count();

        if ($imageUsageCount === 0 && Storage::disk('public')->exists($imagePath)) {
            Storage::disk('public')->delete($imagePath);
        }

        try {
            // Log the stock transaction
            StockTransaction::create([
                'product_id' => $product->id,
                'transaction_type' => 'Deleted',
                'quantity' => $product->stock_quantity ?? 0, // Fallback to 0 if undefined
                'transaction_date' => now(),
                'supplier_id' => $product->supplier_id ?? null, // Handle potential null value
            ]);
        } catch (\Exception $e) {
            // Log error and return a failure message
            report($e);
            return redirect()->route('products.index')->withErrors('Failed to log stock transaction. Please try again.');
        }

        // Delete the product
        $product->delete();

        return redirect()->route('products.index')->banner('Product Deleted successfully.');
    }


    public function getNextBatchNo(Request $request)
    {
        $code = $request->input('code');

        $latestBatch = Product::where('code', $code)
            ->orderBy('created_at', 'desc')
            ->pluck('batch_no')
            ->filter()
            ->map(function ($batch) {
                if (preg_match('/batch(\d+)/i', $batch, $matches)) {
                    return (int) $matches[1];
                }
                return 0;
            })
            ->max();

        $nextBatch = 'batch' . (($latestBatch ?? 0) + 1);

        return response()->json(['next_batch_no' => $nextBatch]);
    }

    public function uploadCsv(Request $request)
    {
        $request->validate([
            'csv_file' => 'required|mimes:csv,txt|max:2048',
        ]);

        $file = $request->file('csv_file');

        $data = array_map('str_getcsv', file($file));

        // Normalize header row
        $headers = array_map('strtolower', array_map('trim', $data[0]));
        unset($data[0]);

        foreach ($data as $row) {
            $row = array_map('trim', $row);

            // Skip empty rows
            if (count(array_filter($row)) === 0) {
                continue;
            }

            Product::create([
                'name' => $row[0] ?? null,
                'code' => $row[1] ?? null,
                'cost_price' => is_numeric($row[2]) ? $row[2] : 0,
                'selling_price' => is_numeric($row[3]) ? $row[3] : 0,
                'discount' => is_numeric($row[4]) ? $row[4] : 0,
                'discounted_price' => is_numeric($row[5]) ? $row[5] : 0,
                'stock_quantity' => is_numeric($row[6]) ? $row[6] : 0,
                'purchase_date' => !empty($row[7]) ? Carbon::parse($row[7]) : null,
                'expire_date' => !empty($row[8]) ? Carbon::parse($row[8]) : null,
                'barcode' => $row[9] ?? null,
                'batch_no' => $row[10] ?? null,
                'preorder_level_qty' => is_numeric($row[11] ?? null) ? $row[11] : 0,
                'expiry_date_margin' => is_numeric($row[12] ?? null) ? $row[12] : 0,
                'whole_price' => is_numeric($row[13] ?? null) ? $row[13] : 0,
                'wholesale_discount' => is_numeric($row[14] ?? null) ? $row[14] : 0,
                'final_whole_price' => is_numeric($row[15] ?? null) ? $row[15] : 0,
            ]);
        }

        return back()->with('success', 'CSV uploaded and products saved successfully.');
    }







 public function getPromotionItems($productId)
    {
        // Fetch promotion items where promotion_id equals $productId
        $promotionItems = PromotionItem::where('promotion_id', $productId)
            ->with('product') // Include related product details
            ->get();

        // Check if any promotion items are found
        if ($promotionItems->isEmpty()) {
            return response()->json(['error' => 'No promotion items found for this promotion ID.'], 404);
        }

        return response()->json([
            'promotion_items' => $promotionItems,
        ]);
    }




  public function addPromotion(Request $request)
    {
        $allcategories = Category::with('parent')->get()->map(function ($category) {
            $category->hierarchy_string = $category->hierarchy_string; // Access it
            return $category;
        });
        $colors = Color::orderBy('created_at', 'desc')->get();
        $sizes = Size::orderBy('created_at', 'desc')->get();


        return Inertia::render('Products/Promotions', [
            'allcategories' => $allcategories,
            'colors' => $colors,
            'sizes' => $sizes,
        ]);
    }

    




 public function submitPromotion(Request $request)
{
    if (!Gate::allows('hasRole', ['Admin'])) {
        abort(403, 'Unauthorized');
    }

    $validated = $request->validate([
        'category_id'       => 'required|exists:categories,id',
        'name'              => 'required|string|max:255',
        'size_id'           => 'nullable|exists:sizes,id',
        'color_id'          => 'nullable|exists:colors,id',
        'cost_price'        => 'required|numeric|min:0',
        'selling_price'     => 'required|numeric|min:0',
        'discounted_price'  => 'nullable|numeric|min:0',
        'stock_quantity'    => 'required|integer|min:0',
        'discount'          => 'nullable|numeric|min:0|max:100',
        'supplier_id'       => 'nullable|exists:suppliers,id',
        'barcode'           => ['nullable','string',\Illuminate\Validation\Rule::unique('products','barcode')->whereNull('deleted_at')],
        'image'             => 'nullable|file|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
        'description'       => 'nullable|string',
        'products'                 => 'required|array|min:1',
        'products.*.id'            => 'required|exists:products,id',
        'products.*.quantity'      => 'required|integer|min:1',
    ], [
        'category_id.required' => 'Category is required.',
        'category_id.exists'   => 'The selected category is invalid.',
    ]);

    try {
        return \DB::transaction(function () use ($request, $validated) {
            $data = $validated;

            if ($request->hasFile('image')) {
                $ext  = $request->file('image')->getClientOriginalExtension();
                $name = 'product_' . now()->format('YmdHis') . '.' . $ext;
                $path = $request->file('image')->storeAs('products', $name, 'public');
                $data['image'] = 'storage/' . $path;
            }

            if (empty($data['barcode'])) {
                $data['barcode'] = $this->generateUniqueCode(12);
            }

            $items = collect($data['products'])
                ->groupBy('id')
                ->map(fn($g) => ['id' => $g->first()['id'], 'quantity' => $g->sum('quantity')])
                ->values()
                ->all();

            unset($data['products']);

            foreach ($items as $i) {
                $p = \App\Models\Product::lockForUpdate()->find($i['id']);
                if (!$p || $p->stock_quantity < $i['quantity']) {
                    abort(422, 'Insufficient stock for product ID '.$i['id']);
                }
            }

            $data['is_promotion']   = true;
            $data['total_quantity'] = (int)($data['stock_quantity'] ?? 0);

            $promotion = \App\Models\Product::create($data);
            $promotion->update(['code' => 'PROD-' . $promotion->id]);

            foreach ($items as $i) {
                \App\Models\PromotionItem::create([
                    'product_id'   => $i['id'],
                    'promotion_id' => $promotion->id,
                    'quantity'     => (int)$i['quantity'],
                ]);
            }

            foreach ($items as $i) {
                $p = \App\Models\Product::lockForUpdate()->find($i['id']);
                $p->stock_quantity  = (int)$p->stock_quantity - (int)$i['quantity'];
                $p->total_quantity  = (int)($p->total_quantity ?? $p->stock_quantity) - (int)$i['quantity'];
                if ($p->total_quantity < 0) $p->total_quantity = 0;
                $p->save();

                \App\Models\StockTransaction::create([
                    'product_id'       => $p->id,
                    'transaction_type' => 'Deducted',
                    'quantity'         => (int)$i['quantity'],
                    'transaction_date' => now(),
                    'supplier_id'      => $validated['supplier_id'] ?? null,
                ]);
            }

            return redirect()->route('products.index')->banner('Promotion created successfully');
        });
    } catch (\Throwable $e) {
        \Log::error('Error creating promotion', ['error' => $e->getMessage()]);
        return back()->with('error', 'An error occurred while creating the promotion. Please try again.')->withInput();
    }
}







}
