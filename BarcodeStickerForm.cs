using System;
using System.Drawing;
using System.Windows.Forms;
using Microsoft.VisualBasic;

namespace UzrsInventory.BarcodeSticker
{
    public partial class BarcodeStickerForm : Form
    {
        public BarcodeStickerForm()
        {
            InitializeComponent();
        }

        private void BarcodeStickerForm_Load(object sender, EventArgs e)
        {
            try
            {
                // Apply modern styling
                ApplyModernStyling();

                // Set focus to item name
                txtItemName.Focus();
            }
            catch (Exception ex)
            {
                MessageBox.Show($"Error initializing form: {ex.Message}", "Error", 
                    MessageBoxButtons.OK, MessageBoxIcon.Error);
            }
        }

        private void ApplyModernStyling()
        {
            try
            {
                // Apply modern button styling
                ApplyModernButtonStyle(btnPrintSticker);
                ApplyModernButtonStyle(btnClear);
                ApplyModernButtonStyle(btnClose);
            }
            catch (Exception ex)
            {
                // Handle styling errors gracefully
            }
        }

        private void ApplyModernButtonStyle(Button btn)
        {
            try
            {
                btn.FlatStyle = FlatStyle.Flat;
                btn.FlatAppearance.BorderSize = 0;
                btn.Font = new Font("Segoe UI", 10, FontStyle.Regular);
                btn.Cursor = Cursors.Hand;

                // Add hover effects
                btn.MouseEnter += Button_MouseEnter;
                btn.MouseLeave += Button_MouseLeave;
            }
            catch (Exception ex)
            {
                // Handle errors gracefully
            }
        }

        private void Button_MouseEnter(object? sender, EventArgs e)
        {
            try
            {
                if (sender is Button btn)
                {
                    Color currentColor = btn.BackColor;

                    // Slightly brighten the button on hover
                    int newR = Math.Min(255, currentColor.R + 20);
                    int newG = Math.Min(255, currentColor.G + 20);
                    int newB = Math.Min(255, currentColor.B + 20);
                    btn.BackColor = Color.FromArgb(newR, newG, newB);
                }
            }
            catch (Exception ex)
            {
                // Handle errors gracefully
            }
        }

        private void Button_MouseLeave(object? sender, EventArgs e)
        {
            try
            {
                if (sender is Button btn)
                {
                    // Reset to original colors based on button type
                    if (btn == btnPrintSticker)
                        btn.BackColor = Color.FromArgb(0, 150, 136);
                    else if (btn == btnClear)
                        btn.BackColor = Color.FromArgb(255, 152, 0);
                    else if (btn == btnClose)
                        btn.BackColor = Color.FromArgb(244, 67, 54);
                }
            }
            catch (Exception ex)
            {
                // Handle errors gracefully
            }
        }

        private void btnPrintSticker_Click(object sender, EventArgs e)
        {
            try
            {
                StickerData? stickerData = BuildStickerDataForPrinting();
                if (stickerData == null)
                {
                    return;
                }

                string quantityInput = Interaction.InputBox(
                    "Enter the number of stickers to print:",
                    "Print Stickers",
                    "1");

                if (string.IsNullOrWhiteSpace(quantityInput))
                {
                    return;
                }

                if (!int.TryParse(quantityInput, out int quantity) || quantity <= 0)
                {
                    MessageBox.Show("Please enter a valid quantity greater than zero.", 
                        "Invalid Quantity", MessageBoxButtons.OK, MessageBoxIcon.Warning);
                    return;
                }

                StickerLayout layout = quantity == 1 ? StickerLayout.SingleLabel : StickerLayout.Auto;

                var printer = new StickerPrinter();
                printer.Print(stickerData, quantity, layout);

                MessageBox.Show("Sticker print job sent to printer.", "Success", 
                    MessageBoxButtons.OK, MessageBoxIcon.Information);
            }
            catch (Exception ex)
            {
                MessageBox.Show($"Error printing sticker: {ex.Message}", "Print Error", 
                    MessageBoxButtons.OK, MessageBoxIcon.Error);
            }
        }

        private StickerData? BuildStickerDataForPrinting()
        {
            string itemName = txtItemName.Text.Trim();
            if (string.IsNullOrEmpty(itemName))
            {
                MessageBox.Show("Please enter an item name before printing stickers.", 
                    "Missing Information", MessageBoxButtons.OK, MessageBoxIcon.Warning);
                txtItemName.Focus();
                return null;
            }

            string barcodeValue = txtBarcode.Text.Trim();

            if (string.IsNullOrEmpty(barcodeValue))
            {
                barcodeValue = txtItemCode.Text.Trim();
            }

            if (string.IsNullOrEmpty(barcodeValue))
            {
                barcodeValue = txtSKU.Text.Trim();
            }

            if (string.IsNullOrEmpty(barcodeValue))
            {
                MessageBox.Show("Please enter an item code, barcode, or SKU before printing stickers.", 
                    "Missing Information", MessageBoxButtons.OK, MessageBoxIcon.Warning);
                txtItemCode.Focus();
                return null;
            }

            string mrpValue = txtMRP.Text.Trim();
            if (string.IsNullOrEmpty(mrpValue))
            {
                mrpValue = txtSalesPrice.Text.Trim();
            }

            string trendPriceValue = txtSalesPrice.Text.Trim();
            if (string.IsNullOrEmpty(trendPriceValue))
            {
                trendPriceValue = mrpValue;
            }

            string sizeValue = string.Empty;
            if (!string.IsNullOrEmpty(txtDescription.Text.Trim()))
            {
                sizeValue = txtDescription.Text.Trim();
            }
            else if (!string.IsNullOrEmpty(cmbUnit.Text.Trim()))
            {
                sizeValue = cmbUnit.Text.Trim();
            }

            var stickerData = new StickerData
            {
                ItemName = itemName,
                Barcode = barcodeValue,
                Mrp = mrpValue,
                TrendPrice = trendPriceValue,
                Size = sizeValue
            };

            return stickerData;
        }

        private void btnClear_Click(object sender, EventArgs e)
        {
            ClearForm();
            txtItemName.Focus();
        }

        private void ClearForm()
        {
            txtItemCode.Text = "";
            txtItemName.Text = "";
            txtSKU.Text = "";
            txtBarcode.Text = "";
            txtDescription.Text = "";
            txtMRP.Text = "0.00";
            txtSalesPrice.Text = "0.00";

            if (cmbUnit.Items.Count > 0)
                cmbUnit.SelectedIndex = 0;
        }

        private void btnClose_Click(object sender, EventArgs e)
        {
            this.Close();
        }

        // Format numeric fields on leave
        private void txtMRP_Leave(object sender, EventArgs e)
        {
            FormatCurrencyField(txtMRP);
        }

        private void txtSalesPrice_Leave(object sender, EventArgs e)
        {
            FormatCurrencyField(txtSalesPrice);
        }

        private void FormatCurrencyField(TextBox field)
        {
            try
            {
                if (double.TryParse(field.Text, out double value))
                {
                    field.Text = value.ToString("F2");
                }
                else
                {
                    field.Text = "0.00";
                }
            }
            catch (Exception)
            {
                field.Text = "0.00";
            }
        }

        // Key press validation for numeric fields
        private void NumericField_KeyPress(object sender, KeyPressEventArgs e)
        {
            // Allow digits, decimal point, backspace, and delete
            if (!char.IsDigit(e.KeyChar) && e.KeyChar != '.' && e.KeyChar != '\b' && e.KeyChar != (char)127)
            {
                e.Handled = true;
            }

            // Allow only one decimal point
            if (e.KeyChar == '.' && sender is TextBox textBox && textBox.Text.Contains("."))
            {
                e.Handled = true;
            }
        }
    }
}
