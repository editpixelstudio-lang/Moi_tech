# Barcode Sticker Generator for C# Project

This module provides barcode sticker printing functionality ported from the VB.NET project.

## Files Included

### Source Files
- **StickerPrinter.cs** - Core printing logic and template processing
- **BarcodeStickerForm.cs** - Windows Form for sticker data entry
- **BarcodeStickerForm.Designer.cs** - Form designer code

### Template Files
- **op1.prn** - Single label template (1 sticker per page)
- **op2.prn** - Auto layout template (2 stickers per page)

## Features

✅ **Item Information Entry**
- Item Code, SKU, Barcode
- Item Name
- Description
- Unit selection

✅ **Pricing Information**
- MRP (Maximum Retail Price)
- Trend Price (Sales Price)

✅ **Printing Options**
- Single label printing
- Auto layout for multiple stickers
- Dynamic quantity selection
- Template-based printing using PRN files

## Usage

### Opening the Form

```csharp
using UzrsInventory.BarcodeSticker;

// Create and show the form
var stickerForm = new BarcodeStickerForm();
stickerForm.ShowDialog();
```

### Programmatic Printing

```csharp
using UzrsInventory.BarcodeSticker;

// Create sticker data
var stickerData = new StickerData
{
    ItemName = "Sample Product",
    Barcode = "1234567890",
    Mrp = "100.00",
    TrendPrice = "89.00",
    Size = "PCS",
    CompanyName = "Trend Makers"
};

// Print stickers
var printer = new StickerPrinter();
printer.Print(stickerData, quantity: 5, StickerLayout.Auto);
```

## PRN Template Format

The PRN files use thermal printer command language with placeholders:

- `lblItemName` - Replaced with item name
- `A1B2C3D4` - Replaced with barcode value
- `lblMRP` - Replaced with MRP value
- `lblTMRs` - Replaced with trend/sales price
- `lblSize` - Replaced with unit/size information
- `Trend Makers` - Company name

## Integration with Main Application

To add a menu item or button to open the sticker form:

```csharp
private void btnPrintSticker_Click(object sender, EventArgs e)
{
    using (var stickerForm = new BarcodeStickerForm())
    {
        stickerForm.ShowDialog();
    }
}
```

## Dependencies

- .NET 8.0 Windows Forms
- Microsoft.VisualBasic (for InputBox dialog)

## Notes

- PRN files are copied to output directory automatically during build
- USB printer DLL integration is prepared but not yet implemented
- The form includes modern Material Design-inspired styling
- All numeric fields include proper validation and formatting

## Reference

This implementation is ported from the VB.NET BarcodeSticker project located at:
`d:\Billing Web\UZRS-inventory-C#\BarcodeSticker\`

Key VB files referenced:
- `frmItems.vb` - Original items form with print sticker functionality
- `StickerPrinter.vb` - Original printer class
- `op1.prn` and `op2.prn` - Template files from bin\Debug folder
