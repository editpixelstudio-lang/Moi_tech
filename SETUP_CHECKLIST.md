# Barcode Printing Setup Checklist

## ✅ Setup Complete!

Your PHP and C# projects are now integrated for barcode printing. Here's what was implemented:

## Files Changed

### C# Project (`Refer-VBproj\UzrsInventory.CSharp\`)

✅ **MainForm.cs**
- Added WebMessageReceived event handler
- Added HandlePrintBarcodeRequest method
- Added JSON serialization support
- Integrated with StickerPrinter class

### PHP Project (`UZRS-inventory\`)

✅ **js/csharp-bridge.js** (NEW)
- JavaScript bridge for C# communication
- `printBarcodeSticker()` function
- Environment detection

✅ **items/add-item.php**
- Added Print Barcode button
- Added print button to product cards
- Updated SQL query for barcode data
- Included csharp-bridge.js script

✅ **items/js/add-item.js**
- Added `printCurrentItemBarcode()` function
- Added `printProductBarcode()` function
- Added `promptQuantity()` modal
- Added `printBarcode()` function

✅ **items/css/add-item.css**
- Styled `.btn-print-barcode` button
- Styled `.btn-print-card-icon` button
- Updated `.form-actions` layout

## How to Test

### 1. Build the C# Project

```powershell
cd "d:\Billing Web\UZRS-inventory-C#\Refer-VBproj\UzrsInventory.CSharp"
dotnet build
```

### 2. Ensure PRN Template Files Exist

Make sure these files are in your application directory:
- `op1.prn` (single label template)
- `op2.prn` (multi-label template)

### 3. Run the Application

```powershell
dotnet run
```

### 4. Test Printing

1. Navigate to Items → Add Item
2. Fill in item details (minimum: Item Name, Barcode)
3. Click **"Print Barcode"** button
4. Enter quantity (e.g., 5)
5. Click "Print"
6. Check printer output

### Alternative: Print from Product List

1. Find any product in the right column
2. Click the blue **printer icon**
3. Enter quantity
4. Click "Print"

## Visual Guide

### Main Print Button Location
```
┌─────────────────────────────────────┐
│        Add New Item Form            │
├─────────────────────────────────────┤
│                                     │
│  Item Name: [_______________]       │
│  Barcode:   [_______________]       │
│  ...                                │
│                                     │
│  ┌──────────┐  ┌──────────────┐   │
│  │   SAVE   │  │ PRINT BARCODE │   │  ← Here
│  └──────────┘  └──────────────┘   │
└─────────────────────────────────────┘
```

### Product Card Print Button
```
┌────────────────────────┐
│  Product Name          │
│  ₹99.00                │
│             [🖨] [🗑]  │  ← Blue printer icon
└────────────────────────┘
```

## What Happens When You Click Print?

1. **Validation**: Checks if item name and barcode are filled
2. **Quantity Modal**: Popup asks "How many stickers?"
3. **Send to C#**: JavaScript sends data via WebView2 bridge
4. **C# Processing**: MainForm receives message → calls StickerPrinter
5. **PRN Template**: Loads appropriate template (op1.prn or op2.prn)
6. **Printer Output**: Sends formatted sticker to USB printer
7. **Confirmation**: Shows success/error message

## Data Sent to Printer

```json
{
  "itemName": "Product Name",
  "barcode": "123456789",
  "mrp": "100.00",
  "salesPrice": "90.00",
  "size": "Medium",
  "companyName": "Trend Makers"
}
```

## Troubleshooting

### ❌ "Barcode printing is only available in the desktop application"
**Solution**: You're running in a web browser. Use the C# desktop app.

### ❌ "Template file not found: op1.prn"
**Solution**: Copy `op1.prn` and `op2.prn` to the application directory.

### ❌ No response after clicking Print
**Solution**: 
1. Check browser console (F12) for errors
2. Verify WebView2 is initialized in C#
3. Check that `Core_WebMessageReceived` event is registered

### ❌ Print button doesn't show
**Solution**: Make sure `csharp-bridge.js` is loaded. Check browser console for:
```
C# Bridge initialized. Running in C# app: true
```

## Browser Console Commands

Open browser console (F12) and test:

```javascript
// Check if running in C# app
console.log(window.isInCSharpApp);  // Should be true

// Test print function
window.printBarcodeSticker({
    itemName: 'Test Product',
    barcode: '123456789',
    mrp: '100.00',
    salesPrice: '90.00',
    size: 'Medium'
}, 1);
```

## Next Steps

### Option 1: Add Print to Other Pages
You can add barcode printing to other pages:
1. Include `csharp-bridge.js`
2. Add print button
3. Call `window.printBarcodeSticker()`

### Option 2: Customize Sticker Design
1. Edit `op1.prn` and `op2.prn` files
2. Change label layout, fonts, sizes
3. Test with your printer

### Option 3: Add Batch Printing
Modify the code to:
- Select multiple items
- Print all at once
- Auto-generate quantities

## Support Files

- 📄 `BARCODE_PRINTING_INTEGRATION.md` - Detailed technical documentation
- 📁 `BarcodeSticker/` - C# printer classes
- 📁 `js/` - JavaScript bridge

## Questions?

Check the detailed documentation:
```
BARCODE_PRINTING_INTEGRATION.md
```

Happy Printing! 🖨️✨
