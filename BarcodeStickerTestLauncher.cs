using System;
using System.Windows.Forms;
using UzrsInventory.BarcodeSticker;

namespace UzrsInventory;

/// <summary>
/// Simple test launcher for the Barcode Sticker Form
/// To test the form, change Program.cs Main method to:
/// Application.Run(new BarcodeStickerForm());
/// </summary>
internal static class BarcodeStickerTestLauncher
{
    // Main method commented out to avoid multiple entry points
    // Uncomment this and comment out Program.cs Main to test the barcode form standalone
    /*
    [STAThread]
    private static void Main()
    {
        ApplicationConfiguration.Initialize();
        
        // Launch the barcode sticker form directly for testing
        Application.Run(new BarcodeStickerForm());
    }
    */
    
    /// <summary>
    /// Launch the barcode sticker form from anywhere in the application
    /// </summary>
    public static void Launch()
    {
        using var form = new BarcodeStickerForm();
        form.ShowDialog();
    }
}
