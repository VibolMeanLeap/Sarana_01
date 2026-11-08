<?php

namespace App\Http\Controllers;

use App\Models\AccountingCategory;
use Illuminate\Http\Request;

class AccountingCategoryController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
        ]);

        AccountingCategory::create([
            'name' => trim($validated['name']),
            'id_type' => 2,
        ]);

        return back()->with('success', 'Expense category added.');
    }

    public function destroy(AccountingCategory $category)
    {
        if ((int) $category->id_type !== 2) {
            return back()->with('error', 'Only expense categories can be removed from this workspace.');
        }

        if ($category->transactions()->exists()) {
            return back()->with('error', 'This category is already used by finance transactions. Keep it to preserve the accounting history.');
        }

        $category->delete();

        return back()->with('success', 'Expense category deleted.');
    }
}
