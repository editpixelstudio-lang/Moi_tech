using System;
using System.Windows.Forms;
using UzrsInventory.BarcodeSticker;

namespace UzrsInventory.Examples
{
    /// <summary>
    /// Example integration of Barcode Sticker functionality
    /// </summary>
    public class BarcodeStickerExample
    {
        /// <summary>
        /// Example 1: Show the sticker form as a dialog
        /// </summary>
        public static void ShowStickerFormExample()
        {
            using (var stickerForm = new BarcodeStickerForm())
            {
                stickerForm.ShowDialog();
            }
        }

        /// <summary>
        /// Example 2: Print sticker programmatically with predefined data
        /// </summary>
        public static void PrintStickerProgrammatically()
        {
            // Create sticker data
            var stickerData = new StickerData
            {
                ItemName = "Premium Coffee Beans",
                Barcode = "8901234567890",
                Mrp = "450.00",
                TrendPrice = "399.00",
                Size = "500g",
                CompanyName = "Trend Makers"
            };

            // Print 5 stickers using auto layout
            var printer = new StickerPrinter();
            printer.Print(stickerData, quantity: 5, StickerLayout.Auto);
        }

        /// <summary>
        /// Example 3: Print single sticker
        /// </summary>
        public static void PrintSingleSticker(string itemName, string barcode, string mrp)
        {
            var stickerData = new StickerData
            {
                ItemName = itemName,
                Barcode = barcode,
                Mrp = mrp,
                TrendPrice = mrp, // Use MRP as sales price
                Size = "PCS"
            };

            var printer = new StickerPrinter();
            printer.Print(stickerData, quantity: 1, StickerLayout.SingleLabel);
        }

        /// <summary>
        /// Example 4: Bulk print stickers for multiple items
        /// </summary>
        public static void BulkPrintStickers()
        {
            // Sample items
            var items = new[]
            {
                new { Name = "Item A", Barcode = "1001", MRP = "100.00", Qty = 2 },
                new { Name = "Item B", Barcode = "1002", MRP = "150.00", Qty = 3 },
                new { Name = "Item C", Barcode = "1003", MRP = "200.00", Qty = 1 }
            };

            var printer = new StickerPrinter();

            foreach (var item in items)
            {
                var stickerData = new StickerData
                {
                    ItemName = item.Name,
                    Barcode = item.Barcode,
                    Mrp = item.MRP,
                    TrendPrice = item.MRP,
                    Size = "PCS"
                };

                // Print based on quantity
                var layout = item.Qty == 1 ? StickerLayout.SingleLabel : StickerLayout.Auto;
                printer.Print(stickerData, item.Qty, layout);
            }

            MessageBox.Show("Bulk printing completed!", "Success", 
                MessageBoxButtons.OK, MessageBoxIcon.Information);
        }

        /// <summary>
        /// Example 5: Add print sticker button to an existing form
        /// </summary>
        public static void AddPrintButtonToExistingForm(Form parentForm)
        {
            var btnPrintSticker = new Button
            {
                Text = "🎟️ Print Sticker",
                Size = new System.Drawing.Size(150, 40),
                Location = new System.Drawing.Point(20, 20),
                BackColor = System.Drawing.Color.FromArgb(0, 150, 136),
                ForeColor = System.Drawing.Color.White,
                FlatStyle = FlatStyle.Flat
            };

            btnPrintSticker.FlatAppearance.BorderSize = 0;
            btnPrintSticker.Click += (sender, e) =>
            {
                ShowStickerFormExample();
            };

            parentForm.Controls.Add(btnPrintSticker);
        }

        /// <summary>
        /// Example 6: Print sticker from database item
        /// </summary>
        public static void PrintFromDatabaseItem(dynamic dbItem)
        {
            var stickerData = new StickerData
            {
                ItemName = dbItem.ItemName ?? "Unknown Item",
                Barcode = dbItem.Barcode ?? dbItem.ItemCode ?? "",
                Mrp = dbItem.MRP?.ToString("F2") ?? "0.00",
                TrendPrice = dbItem.SalesPrice?.ToString("F2") ?? "0.00",
                Size = dbItem.Unit ?? dbItem.Description ?? "",
                CompanyName = "Trend Makers"
            };

            // Ask user for quantity
            var quantityInput = Microsoft.VisualBasic.Interaction.InputBox(
                "Enter the number of stickers to print:",
                "Print Stickers",
                "1");

            if (int.TryParse(quantityInput, out int quantity) && quantity > 0)
            {
                var layout = quantity == 1 ? StickerLayout.SingleLabel : StickerLayout.Auto;
                var printer = new StickerPrinter();
                printer.Print(stickerData, quantity, layout);
            }
        }
    }
}
