# Quick Reference: PHP to C# Barcode Printing

## JavaScript Function (PHP Side)

```javascript
// Print barcode from your PHP page
window.printBarcodeSticker({
    itemName: 'Product Name',      // Required
    barcode: '123456789',          // Required
    mrp: '100.00',                 // Optional (default: '0.00')
    salesPrice: '90.00',           // Optional (default: '0.00')
    size: 'Medium',                // Optional (default: '')
    companyName: 'Trend Makers'    // Optional (default: 'Trend Makers')
}, quantity)
.then(() => {
    console.log('Print success!');
})
.catch(error => {
    console.error('Print failed:', error.message);
});
```

## Environment Detection

```javascript
if (window.isInCSharpApp) {
    // Running in C# desktop app
    // Print button can be enabled
} else {
    // Running in web browser
    // Hide or disable print button
}
```

## C# Event Handler (C# Side)

```csharp
// In MainForm.cs - ConfigureWebView method
core.WebMessageReceived += Core_WebMessageReceived;

// Handler receives messages from PHP
private void Core_WebMessageReceived(object? sender, CoreWebView2WebMessageReceivedEventArgs e)
{
    var message = JsonSerializer.Deserialize<Dictionary<string, JsonElement>>(e.TryGetWebMessageAsString());
    
    if (message["action"].GetString() == "printBarcode") {
        HandlePrintBarcodeRequest(message);
    }
}
```

## Message Format

### From PHP to C# (Request)
```json
{
  "action": "printBarcode",
  "quantity": 5,
  "data": {
    "itemName": "Product",
    "barcode": "123",
    "mrp": "100.00",
    "salesPrice": "90.00",
    "size": "M",
    "companyName": "Company"
  }
}
```

### From C# to PHP (Response)
```json
{
  "success": true,
  "message": "Barcode sticker sent to printer successfully!"
}
```

## PHP Button HTML

```html
<!-- Main form print button -->
<button type="button" class="btn-print-barcode" onclick="printCurrentItemBarcode()">
    <i class="fas fa-print"></i> Print Barcode
</button>

<!-- Product card print button -->
<button class="btn-print-card-icon" onclick="printProductBarcode(123)">
    <i class="fas fa-print"></i>
</button>
```

## CSS Classes

```css
.btn-print-barcode {
    background: #3b82f6;    /* Blue */
    color: white;
    padding: 0.55rem 2rem;
    border-radius: 6px;
}

.btn-print-card-icon {
    background: #3b82f6;    /* Blue */
    color: white;
    width: 32px;
    height: 32px;
}
```

## Common Use Cases

### 1. Print from Form
```javascript
function printCurrentItemBarcode() {
    const itemData = {
        itemName: $('#itemName').val(),
        barcode: $('#barcode').val(),
        mrp: $('#mrp').val(),
        salesPrice: $('#salesPrice').val()
    };
    
    window.printBarcodeSticker(itemData, 1);
}
```

### 2. Print from List/Grid
```javascript
function printProductBarcode(itemId) {
    const card = $(`.product-card[data-id="${itemId}"]`);
    const itemData = {
        itemName: card.data('itemname'),
        barcode: card.data('barcode'),
        mrp: card.data('mrp'),
        salesPrice: card.data('salesprice')
    };
    
    window.printBarcodeSticker(itemData, 1);
}
```

### 3. Batch Print (Multiple Items)
```javascript
function printBatch(items) {
    items.forEach(item => {
        window.printBarcodeSticker({
            itemName: item.name,
            barcode: item.code,
            mrp: item.price,
            salesPrice: item.price
        }, item.quantity);
    });
}
```

## Error Handling

```javascript
window.printBarcodeSticker(itemData, quantity)
    .then(() => {
        // Success
        showNotification('Printed successfully!', 'success');
    })
    .catch(error => {
        // Error
        if (error.message.includes('desktop application')) {
            showNotification('Use desktop app to print', 'error');
        } else {
            showNotification(error.message, 'error');
        }
    });
```

## Required Files Checklist

### PHP Project
- ✅ `js/csharp-bridge.js` - Bridge script
- ✅ Include in page: `<script src="../js/csharp-bridge.js"></script>`

### C# Project
- ✅ `MainForm.cs` - WebMessageReceived handler
- ✅ `BarcodeSticker/StickerPrinter.cs` - Printer class
- ✅ `op1.prn` - Single label template
- ✅ `op2.prn` - Multi-label template

## Testing Commands

### Browser Console (F12)
```javascript
// Check environment
console.log('In C# App:', window.isInCSharpApp);

// Test print
window.printBarcodeSticker({
    itemName: 'Test',
    barcode: '999'
}, 1);
```

### PowerShell (Build C#)
```powershell
cd "d:\Billing Web\UZRS-inventory-C#\Refer-VBproj\UzrsInventory.CSharp"
dotnet build
dotnet run
```

## Debugging Tips

1. **Check if bridge loaded**: Console should show "C# Bridge initialized"
2. **Check WebView2**: C# app must use WebView2, not WebBrowser control
3. **Check event registration**: `Core_WebMessageReceived` must be registered
4. **Check JSON**: Ensure data is valid JSON format
5. **Check templates**: PRN files must exist in app directory

## Performance Notes

- Print function is **asynchronous** (returns Promise)
- Each print call is **independent**
- For batch printing, consider adding **delay** between prints
- Bridge has **no rate limiting** - implement if needed

## Security Considerations

- ✅ Print only works in C# app (not in browser)
- ✅ Input validation on C# side
- ✅ No direct file system access from PHP
- ✅ Controlled communication via WebView2 bridge

---

**Documentation**: See `BARCODE_PRINTING_INTEGRATION.md` for full details
**Setup Guide**: See `SETUP_CHECKLIST.md` for installation steps
