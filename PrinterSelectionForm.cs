using System;
using System.Drawing;
using System.Drawing.Printing;
using System.Windows.Forms;

namespace UzrsInventory
{
    public class PrinterSelectionForm : Form
    {
        private ComboBox cmbPrinters;
        private Button btnSave;
        private Label lblInstruction;
        
        public string SelectedPrinter { get; private set; } = string.Empty;

        public PrinterSelectionForm(string defaultPrinter = null)
        {
            InitializeComponent();
            LoadPrinters(defaultPrinter);
        }

        private void InitializeComponent()
        {
            this.cmbPrinters = new ComboBox();
            this.btnSave = new Button();
            this.lblInstruction = new Label();
            this.SuspendLayout();

            // 
            // lblInstruction
            // 
            this.lblInstruction.AutoSize = true;
            this.lblInstruction.Location = new Point(20, 20);
            this.lblInstruction.Text = "Please select the Thermal Printer:";
            this.lblInstruction.Font = new Font("Segoe UI", 10F, FontStyle.Bold);

            // 
            // cmbPrinters
            // 
            this.cmbPrinters.DropDownStyle = ComboBoxStyle.DropDownList;
            this.cmbPrinters.FormattingEnabled = true;
            this.cmbPrinters.Location = new Point(20, 50);
            this.cmbPrinters.Size = new Size(300, 30);
            this.cmbPrinters.Font = new Font("Segoe UI", 10F);

            // 
            // btnSave
            // 
            this.btnSave.Text = "Save Setting";
            this.btnSave.Location = new Point(20, 100);
            this.btnSave.Size = new Size(120, 35);
            this.btnSave.Click += new EventHandler(this.BtnSave_Click);
            this.btnSave.BackColor = Color.FromArgb(0, 120, 215);
            this.btnSave.ForeColor = Color.White;
            this.btnSave.FlatStyle = FlatStyle.Flat;
            this.btnSave.Cursor = Cursors.Hand;

            // 
            // PrinterSelectionForm
            // 
            this.ClientSize = new Size(360, 160);
            this.Controls.Add(this.btnSave);
            this.Controls.Add(this.cmbPrinters);
            this.Controls.Add(this.lblInstruction);
            this.FormBorderStyle = FormBorderStyle.FixedDialog;
            this.MaximizeBox = false;
            this.MinimizeBox = false;
            this.StartPosition = FormStartPosition.CenterScreen;
            this.Text = "Printer Setup";
            this.ResumeLayout(false);
            this.PerformLayout();
        }

        private void LoadPrinters(string defaultPrinter)
        {
            foreach (string printer in PrinterSettings.InstalledPrinters)
            {
                cmbPrinters.Items.Add(printer);
            }

            if (cmbPrinters.Items.Count > 0)
            {
                int index = -1;
                
                // Try to select passed default
                if (!string.IsNullOrEmpty(defaultPrinter))
                {
                    index = cmbPrinters.FindString(defaultPrinter);
                }

                // If not found or empty, try system default
                if (index < 0)
                {
                    var settings = new PrinterSettings();
                    index = cmbPrinters.FindString(settings.PrinterName);
                }

                cmbPrinters.SelectedIndex = index >= 0 ? index : 0;
            }
        }

        private void BtnSave_Click(object? sender, EventArgs e)
        {
            if (cmbPrinters.SelectedItem != null)
            {
                SelectedPrinter = cmbPrinters.SelectedItem.ToString() ?? "";
                this.DialogResult = DialogResult.OK;
                this.Close();
            }
            else
            {
                MessageBox.Show("Please select a printer first.", "Error", MessageBoxButtons.OK, MessageBoxIcon.Warning);
            }
        }
    }
}
