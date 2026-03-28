using System;
using System.IO;
using System.Windows.Forms;

namespace UzrsInventory.BarcodeSticker
{
    /// <summary>
    /// Sticker layout options
    /// </summary>
    public enum StickerLayout
    {
        SingleLabel = 1,
        Auto = 2
    }

    /// <summary>
    /// Data structure for sticker information
    /// </summary>
    public class StickerData
    {
        public string ItemName { get; set; } = "";
        public string Barcode { get; set; } = "";
        public string Mrp { get; set; } = "";
        public string TrendPrice { get; set; } = "";
        public string Size { get; set; } = "";
        public string CompanyName { get; set; } = "Trend Makers";
    }

    /// <summary>
    /// Handles printing of barcode stickers using PRN template files
    /// </summary>
    public class StickerPrinter
    {
        public string Print(StickerData data, int quantity, StickerLayout layout, bool showSuccessDialog = true)
        {
            try
            {
                // Validate required data
                if (string.IsNullOrEmpty(data.ItemName))
                {
                    throw new ArgumentException("Item name is required");
                }
                if (string.IsNullOrEmpty(data.Barcode))
                {
                    throw new ArgumentException("Barcode is required");
                }

                string templateFile = "";

                // Determine which template to use
                switch (layout)
                {
                    case StickerLayout.SingleLabel:
                        templateFile = "op1.prn";
                        break;
                    case StickerLayout.Auto:
                        templateFile = "op2.prn";
                        break;
                    default:
                        templateFile = "op1.prn";
                        break;
                }

                // Get the full path to the template file
                string templatePath = Path.Combine(Application.StartupPath, templateFile);

                // For testing without printer: just show success message with data
                string message = $"✅ Barcode Print Request Processed Successfully!\n\n" +
                                $"Item Name: {data.ItemName}\n" +
                                $"Barcode: {data.Barcode}\n" +
                                $"MRP: Rs {data.Mrp}\n" +
                                $"Sales Price: Rs {data.TrendPrice}\n" +
                                $"Size: {data.Size}\n" +
                                $"Company: {data.CompanyName}\n" +
                                $"Quantity: {quantity}\n" +
                                $"Layout: {layout}\n" +
                                $"Template: {templateFile}\n\n" +
                                $"Note: Actual printing is disabled for testing.";

                if (showSuccessDialog)
                {
                    MessageBox.Show(message, "Print Success", 
                        MessageBoxButtons.OK, MessageBoxIcon.Information);
                }

                // Uncomment below for actual printing when printer is available
                /*
                if (!File.Exists(templatePath))
                {
                    MessageBox.Show($"Template file not found: {templateFile}", "Error", 
                        MessageBoxButtons.OK, MessageBoxIcon.Error);
                    return;
                }

                // Read the template
                string template = File.ReadAllText(templatePath);

                // Replace placeholders with actual data
                template = template.Replace("lblItemName", data.ItemName);
                template = template.Replace("A1B2C3D4", data.Barcode);
                template = template.Replace("lblMRP", "Rs " + data.Mrp);
                template = template.Replace("lblTMRs", data.TrendPrice);
                template = template.Replace("lblSize", data.Size);
                template = template.Replace("Trend Makers", data.CompanyName);

                // Save the modified template to a temporary file
                string tempFile = Path.Combine(Application.StartupPath, "temp_sticker.prn");
                File.WriteAllText(tempFile, template);

                // Send to printer
                PrintToUSB(tempFile);
                */

                return message;
            }
            catch (Exception ex)
            {
                MessageBox.Show($"Error printing sticker: {ex.Message}", "Error", 
                    MessageBoxButtons.OK, MessageBoxIcon.Error);
                throw;
            }
        }

        private void PrintToUSB(string filePath)
        {
            try
            {
                // Check if UsbPrint.dll exists
                string dllPath = Path.Combine(Application.StartupPath, "UsbPrint.dll");

                if (File.Exists(dllPath))
                {
                    // TODO: Implement USB printing using UsbPrint.dll
                    // For now, just show a message
                    MessageBox.Show($"USB Printer DLL found. Implement USB printing logic here.\nFile to print: {filePath}", 
                        "Info", MessageBoxButtons.OK, MessageBoxIcon.Information);
                }
                else
                {
                    // Fallback: Save the file and notify user
                    MessageBox.Show($"USB Printer DLL not found.\nSticker file saved to: {filePath}\n" +
                                  "Please manually send this file to your printer.",
                                  "Info", MessageBoxButtons.OK, MessageBoxIcon.Information);
                }
            }
            catch (Exception ex)
            {
                throw new Exception($"Failed to print to USB: {ex.Message}");
            }
        }
    }
}
