# Barcode Printing Integration Guide

## Overview

This document describes the integration between the PHP web application and the C# desktop application for barcode sticker printing.

## Architecture

The system uses **WebView2** to host the PHP web application inside a C# desktop application. Communication between PHP (JavaScript) and C# happens via the WebView2 message bridge.

```
┌─────────────────────────────────────┐
│   C# Desktop Application            │
│   (MainForm.cs)                     │
│                                     │
│   ┌──────────────────────────────┐ │
│   │  WebView2 Control            │ │
│   │                              │ │
│   │  ┌────────────────────────┐ │ │
│   │  │ PHP Web Application    │ │ │
│   │  │ (add-item.php)         │ │ │
│   │  │                        │ │ │
│   │  │ JavaScript Bridge      │ │ │
│   │  │ (csharp-bridge.js)     │ │ │
│   │  └────────────────────────┘ │ │
│   └──────────────────────────────┘ │
│             ↕                       │
│   WebMessageReceived Handler        │
│             ↕                       │
│   ┌──────────────────────────────┐ │
│   │  Barcode Printer             │ │
│   │  (StickerPrinter.cs)         │ │
│   └──────────────────────────────┘ │
└─────────────────────────────────────┘
```

## Files Modified/Created

### C# Application Files

1. **MainForm.cs**
   - Added `Core_WebMessageReceived` event handler
   - Added `HandlePrintBarcodeRequest` method to process print requests
   - Integrated with `StickerPrinter` class

2. **StickerPrinter.cs** (existing)
   - Handles actual barcode printing using PRN templates
   - Supports two layouts: SingleLabel and Auto

### PHP Application Files

1. **js/csharp-bridge.js** (new)
   - JavaScript bridge for C# communication
   - `window.printBarcodeSticker(itemData, quantity)` - Main print function
   - `window.isInCSharpApp` - Detects if running in C# WebView
   - `window.showNotification(message, type)` - Display notifications

2. **items/add-item.php** (modified)
   - Added "Print Barcode" button in form
   - Added print button to each product card
   - Updated SQL query to fetch barcode and unit data
   - Included csharp-bridge.js script

3. **items/js/add-item.js** (modified)
   - `printCurrentItemBarcode()` - Print from main form
   - `printProductBarcode(itemId)` - Print from product card
   - `promptQuantity()` - Modal to select print quantity
   - `printBarcode(itemData, quantity)` - Core print function

4. **items/css/add-item.css** (modified)
   - Styles for `.btn-print-barcode` (main print button)
   - Styles for `.btn-print-card-icon` (product card print button)
   - Updated `.form-actions` to use flexbox layout

## Usage

### From Add Item Form

1. Fill in the item details (at minimum: Item Name and Barcode)
2. Click the **"Print Barcode"** button
3. Enter the quantity of stickers to print
4. Click "Print" in the modal

### From Product List

1. Click the **printer icon** on any product card
2. Enter the quantity of stickers to print
3. Click "Print" in the modal

## Data Flow

### 1. User Clicks Print Button (PHP/JavaScript)

```javascript
// User clicks print button
printCurrentItemBarcode() or printProductBarcode(itemId)
  ↓
// Validate item data
  ↓
// Prompt for quantity
promptQuantity()
  ↓
// Call C# bridge
window.printBarcodeSticker(itemData, quantity)
```

### 2. Bridge Sends Message to C#

```javascript
// csharp-bridge.js
const printData = {
    action: 'printBarcode',
    quantity: 1,
    data: {
        itemName: 'Product Name',
        barcode: '123456789',
        mrp: '100.00',
        salesPrice: '90.00',
        size: 'Medium',
        companyName: 'Trend Makers'
    }
};

chrome.webview.postMessage(printData);
```

### 3. C# Processes Request

```csharp
// MainForm.cs - Core_WebMessageReceived
private void Core_WebMessageReceived(object? sender, CoreWebView2WebMessageReceivedEventArgs e)
{
    var message = JsonSerializer.Deserialize<Dictionary<string, JsonElement>>(messageJson);
    
    if (action == "printBarcode") {
        HandlePrintBarcodeRequest(message);
    }
}
```

### 4. C# Prints Sticker

```csharp
// MainForm.cs - HandlePrintBarcodeRequest
var printer = new StickerPrinter();
printer.Print(stickerData, quantity, layout);
```

### 5. Response Sent Back to PHP

```csharp
// Send success message back
var response = JsonSerializer.Serialize(new {
    success = true,
    message = "Barcode sticker sent to printer successfully!"
});

webView.CoreWebView2.ExecuteScriptAsync($"window.handlePrintResponse({response});");
```

## Item Data Structure

### JavaScript to C# Message Format

```json
{
  "action": "printBarcode",
  "quantity": 1,
  "data": {
    "itemName": "Product Name",
    "barcode": "123456789",
    "mrp": "100.00",
    "salesPrice": "90.00",
    "size": "Medium",
    "companyName": "Trend Makers"
  }
}
```

### C# StickerData Class

```csharp
public class StickerData
{
    public string ItemName { get; set; }
    public string Barcode { get; set; }
    public string Mrp { get; set; }
    public string TrendPrice { get; set; }
    public string Size { get; set; }
    public string CompanyName { get; set; }
}
```

## Features

### ✅ Implemented Features

- Print barcode from add item form
- Print barcode from product cards
- Quantity selection via modal
- Automatic layout selection (SingleLabel for qty=1, Auto for qty>1)
- Detection of C# environment (print button only shows in desktop app)
- Success/error notifications
- Validation of required fields (item name, barcode)
- Fallback to ItemCode if Barcode is empty

### 🎯 Feature Highlights

1. **Environment Detection**: The print button is only active when running in the C# WebView application
2. **Smart Data Handling**: Automatically uses ItemCode if Barcode is not set
3. **User-Friendly Quantity Selection**: Modal popup for entering print quantity
4. **Visual Feedback**: Loading states and toast notifications
5. **Error Handling**: Comprehensive error messages at each step

## Testing

### Test in C# Desktop Application

1. Build and run the C# desktop application
2. Navigate to Items → Add Item page
3. Add a new item with barcode
4. Click "Print Barcode"
5. Verify sticker is sent to printer

### Test in Web Browser (Expected Behavior)

1. Open add-item.php in a web browser
2. Click "Print Barcode"
3. Should show error: "Barcode printing is only available in the desktop application."

## Error Handling

### Common Errors and Solutions

| Error | Cause | Solution |
|-------|-------|----------|
| "Barcode printing is only available in the desktop application" | Running in web browser | Use C# desktop application |
| "Item name and barcode are required" | Missing required fields | Fill in item name and barcode |
| "Failed to communicate with desktop application" | WebView2 bridge error | Check C# event handler registration |
| "Template file not found: op1.prn" | Missing PRN template | Ensure op1.prn and op2.prn exist in application folder |

## Customization

### Change Company Name

Edit `csharp-bridge.js`:
```javascript
companyName: itemData.companyName || 'Your Company Name'
```

### Modify Sticker Layout

Edit PRN template files:
- `op1.prn` - Single label layout
- `op2.prn` - Auto/multiple labels layout

### Add More Data Fields

1. Add field to `StickerData` class in `StickerPrinter.cs`
2. Update `HandlePrintBarcodeRequest` in `MainForm.cs`
3. Update `printBarcodeSticker` function in `csharp-bridge.js`
4. Update `printCurrentItemBarcode` in `add-item.js`

## Future Enhancements

- [ ] Preview sticker before printing
- [ ] Save print history
- [ ] Multiple printer support
- [ ] Custom sticker templates
- [ ] Batch printing from item list
- [ ] QR code support
- [ ] Print settings configuration UI

## Dependencies

### C# Dependencies
- Microsoft.Web.WebView2 (WebView2 control)
- System.Text.Json (JSON serialization)

### PHP Dependencies
- jQuery (DOM manipulation)
- Bootstrap (Modal dialogs)
- modern-messagebox.js (Toast notifications)

## Support

For issues or questions:
1. Check error messages in browser console (F12)
2. Check C# application error dialogs
3. Verify PRN template files exist
4. Ensure barcode data is complete

## Version History

- **v1.0** (2024-11-17)
  - Initial integration
  - Print from add item form
  - Print from product cards
  - Quantity selection
  - Environment detection
