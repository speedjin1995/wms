# Stock Movement Documentation

## Overview

The stock movement system tracks every change to raw stock balance across all modules (Wholesales, Regrading, Packaging, Stock Transfer, Loading). It maintains two tables:

- `raw_stock_balance` — the current live balance per product/grade/company
- `stock_movements` — the full ledger history of every balance change

Every action that affects stock writes one or more rows to `stock_movements` and updates `raw_stock_balance` accordingly.

---

## Table Structure

### raw_stock_balance

Stores the current stock balance per product, grade and company. Updated on every create, edit and delete action.

| Column | Description |
|---|---|
| product_id | The product |
| grade | The grade of the product (e.g. A-GRADE, B-GRADE) |
| company | The company this balance belongs to |
| balance | Current stock balance (stored as varchar, processed as float) |

### stock_movements

The full immutable ledger. Rows are never updated or deleted — only new rows are inserted.

| Column | Description |
|---|---|
| movement_no | Unique identifier, format: SM + YYYYMMDD + 4-digit counter (e.g. SM202606080001) |
| product_id | The product |
| grade | The grade |
| company | The company |
| module | Source module (wholesales, grading, packaging, etc.) |
| source_id | The ID of the record in the source module table (e.g. wholesales.id) |
| movement_type | ADD, MINUS, or REVERSAL |
| status | The transaction status from the source module (RECEIVING, DISPATCH, etc.) |
| quantity | The weight/qty involved in this movement |
| balance_before | Balance before this movement was applied |
| balance_after | Balance after this movement was applied |
| customer | Customer ID from the source record |
| supplier | Supplier ID from the source record |
| original_movement_id | On REVERSAL rows — the id of the movement being reversed |
| edit_ref | On REVERSAL and new entry rows during an edit — the movement_no of the original row, used to group the edit pair together |
| created_by | User who triggered this movement |
| created_date | Timestamp |

---

## Movement Types

| movement_type | When Written | Effect on Balance |
|---|---|---|
| ADD | Create or edit (RECEIVING / INCOMING) | Increases balance |
| MINUS | Create or edit (DISPATCH / OUTGOING / etc.) | Decreases balance |
| REVERSAL | Edit (undo previous) or Delete | Opposite of the movement being reversed |

---

## Status → Direction Mapping

| status | movement_type |
|---|---|
| RECEIVING | ADD |
| INCOMING | ADD |
| DISPATCH | MINUS |
| OUTGOING | MINUS |
| (others) | MINUS |

---

## How Each Action Works

### Create
Writes 1 row (ADD or MINUS) and updates `raw_stock_balance`.

```
CREATE RECEIVING nett=100
→ ADD, qty=100, balance: 0 → 100
```

### Edit
If the quantity has not changed, no movement is written.
If the quantity changed, writes 2 rows:
1. REVERSAL — undoes the previous quantity, `edit_ref` = original `movement_no`
2. ADD/MINUS — applies the new quantity, `edit_ref` = same original `movement_no`

Both rows share the same `edit_ref` so they can be grouped as one edit event in reports.

```
EDIT RECEIVING nett 100 → 110
→ REVERSAL, qty=100, balance: 100 → 0   (edit_ref=SM...0001, original_movement_id=1)
→ ADD,      qty=110, balance: 0 → 110   (edit_ref=SM...0001)
```

### Delete (soft delete)
Finds the latest non-REVERSAL movement per product/grade for the source record and writes a REVERSAL row to undo it. Only the latest movement is targeted because it already reflects the net effect of all previous edits.

```
DELETE RECEIVING (last active movement was ADD 110)
→ REVERSAL, qty=110, balance: 110 → 0
```

---

## Test Scenario — Durian Business (Musang King, A-GRADE)

The following scenario tests the full lifecycle: receive stock, edit it, delete it, receive again, dispatch, edit dispatch, delete dispatch.

### Steps

| # | Action | Details |
|---|---|---|
| 1 | Create RECEIVING | nett = 100 |
| 2 | Edit RECEIVING | nett changed to 110 |
| 3 | Delete RECEIVING | balance should return to 0 |
| 4 | Create RECEIVING | nett = 200 |
| 5 | Create DISPATCH | nett = 100 |
| 6 | Edit DISPATCH | nett changed to 110 |
| 7 | Delete DISPATCH | balance should return to 200 |

### Expected Final Balance: 200

---

### stock_movements Data

| id | movement_no | movement_type | status | qty | balance_before | balance_after | source_id | original_movement_id | edit_ref |
|---|---|---|---|---|---|---|---|---|---|
| 1 | SM202606080001 | ADD | RECEIVING | 100 | 0 | 100 | 7311 | NULL | NULL |
| 2 | SM202606080002 | REVERSAL | RECEIVING | 100 | 100 | 0 | 7311 | 1 | SM202606080001 |
| 3 | SM202606080003 | ADD | RECEIVING | 110 | 0 | 110 | 7311 | NULL | SM202606080001 |
| 4 | SM202606080004 | REVERSAL | RECEIVING | 110 | 110 | 0 | 7311 | 3 | SM202606080003 |
| 5 | SM202606080005 | ADD | RECEIVING | 200 | 0 | 200 | 7312 | NULL | NULL |
| 6 | SM202606080006 | MINUS | DISPATCH | 100 | 200 | 100 | 7313 | NULL | NULL |
| 7 | SM202606080007 | REVERSAL | DISPATCH | 100 | 100 | 200 | 7313 | 6 | SM202606080006 |
| 8 | SM202606080008 | MINUS | DISPATCH | 110 | 200 | 90 | 7313 | NULL | SM202606080006 |
| 9 | SM202606080009 | REVERSAL | DISPATCH | 110 | 90 | 200 | 7313 | 8 | SM202606080008 |

### Row-by-Row Explanation

**Row 1 — Step 1: Create RECEIVING nett=100**
First stock movement ever for this product/grade. Balance starts at 0, goes to 100.
`edit_ref` and `original_movement_id` are NULL because this is a fresh create.

**Row 2 — Step 2: Edit RECEIVING, nett changed from 100 → 110 (REVERSAL)**
Before applying the new quantity, the old ADD of 100 is reversed.
`original_movement_id=1` points to the row being undone.
`edit_ref=SM202606080001` links this row to the edit pair.
Balance goes from 100 back to 0.

**Row 3 — Step 2: Edit RECEIVING, nett changed from 100 → 110 (new ADD)**
The new quantity 110 is applied after the reversal.
`edit_ref=SM202606080001` is the same as row 2, grouping both rows as one edit event.
`original_movement_id` is NULL because this is the new entry, not the reversal.
Balance goes from 0 to 110.

**Row 4 — Step 3: Delete RECEIVING**
The latest non-REVERSAL movement for source_id=7311 was row 3 (ADD 110).
A REVERSAL is written to undo it. Balance goes from 110 back to 0.
`original_movement_id=3` and `edit_ref=SM202606080003` point to row 3.

**Row 5 — Step 4: Create new RECEIVING nett=200**
Fresh receiving on a new wholesale record (source_id=7312). Balance goes from 0 to 200.

**Row 6 — Step 5: Create DISPATCH nett=100**
Stock goes out (source_id=7313). Balance goes from 200 to 100.

**Row 7 — Step 6: Edit DISPATCH, nett changed from 100 → 110 (REVERSAL)**
The old MINUS of 100 is reversed first. Balance goes from 100 back to 200.
`original_movement_id=6` and `edit_ref=SM202606080006`.

**Row 8 — Step 6: Edit DISPATCH, nett changed from 100 → 110 (new MINUS)**
The new quantity 110 is applied. Balance goes from 200 to 90.
`edit_ref=SM202606080006` groups this with row 7 as one edit pair.

**Row 9 — Step 7: Delete DISPATCH**
The latest non-REVERSAL movement for source_id=7313 was row 8 (MINUS 110).
A REVERSAL is written to undo it. Balance goes from 90 back to 200.
`original_movement_id=8` and `edit_ref=SM202606080008`.

---

## Reading the Ledger

### Full Audit View
Show all rows ordered by `id`. Every single action is visible including reversals. Useful for debugging or compliance.

### Adjustment View
To see only the net effect of each edit, group rows by `edit_ref`. The REVERSAL and new entry together show what changed and by how much.

### Current Balance
Always read from `raw_stock_balance.balance`. Do not attempt to recompute from `stock_movements` unless rebuilding for audit purposes — use `balance_after` of the latest row per product/grade.

### Tracing an Edit
Given any `movement_no`, find all rows where `edit_ref = that movement_no` to see the full edit history for that original movement.

---

## Modules Covered

| Module | Supported | Notes |
|---|---|---|
| Wholesales | Yes | RECEIVING (ADD), DISPATCH (MINUS) |
| Regrading | Planned | — |
| Packaging | Planned | — |
| Stock Transfer | Planned | — |
| Loading | Planned | — |
