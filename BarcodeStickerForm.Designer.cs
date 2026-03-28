using System.Drawing;
using System.Windows.Forms;

namespace UzrsInventory.BarcodeSticker
{
    partial class BarcodeStickerForm
    {
        /// <summary>
        /// Required designer variable.
        /// </summary>
        private System.ComponentModel.IContainer components = null;

        /// <summary>
        /// Clean up any resources being used.
        /// </summary>
        /// <param name="disposing">true if managed resources should be disposed; otherwise, false.</param>
        protected override void Dispose(bool disposing)
        {
            if (disposing && (components != null))
            {
                components.Dispose();
            }
            base.Dispose(disposing);
        }

        #region Windows Form Designer generated code

        /// <summary>
        /// Required method for Designer support - do not modify
        /// the contents of this method with the code editor.
        /// </summary>
        private void InitializeComponent()
        {
            this.pnlTopBar = new System.Windows.Forms.Panel();
            this.lblTitle = new System.Windows.Forms.Label();
            this.btnClose = new System.Windows.Forms.Button();
            this.pnlMain = new System.Windows.Forms.Panel();
            this.grpBasicInfo = new System.Windows.Forms.GroupBox();
            this.txtDescription = new System.Windows.Forms.TextBox();
            this.lblDescription = new System.Windows.Forms.Label();
            this.cmbUnit = new System.Windows.Forms.ComboBox();
            this.lblUnit = new System.Windows.Forms.Label();
            this.txtBarcode = new System.Windows.Forms.TextBox();
            this.lblBarcode = new System.Windows.Forms.Label();
            this.txtSKU = new System.Windows.Forms.TextBox();
            this.lblSKU = new System.Windows.Forms.Label();
            this.txtItemName = new System.Windows.Forms.TextBox();
            this.lblItemName = new System.Windows.Forms.Label();
            this.txtItemCode = new System.Windows.Forms.TextBox();
            this.lblItemCode = new System.Windows.Forms.Label();
            this.grpPricing = new System.Windows.Forms.GroupBox();
            this.txtSalesPrice = new System.Windows.Forms.TextBox();
            this.lblSalesPrice = new System.Windows.Forms.Label();
            this.txtMRP = new System.Windows.Forms.TextBox();
            this.lblMRP = new System.Windows.Forms.Label();
            this.pnlButtons = new System.Windows.Forms.Panel();
            this.btnPrintSticker = new System.Windows.Forms.Button();
            this.btnClear = new System.Windows.Forms.Button();
            this.pnlTopBar.SuspendLayout();
            this.pnlMain.SuspendLayout();
            this.grpBasicInfo.SuspendLayout();
            this.grpPricing.SuspendLayout();
            this.pnlButtons.SuspendLayout();
            this.SuspendLayout();
            // 
            // pnlTopBar
            // 
            this.pnlTopBar.BackColor = System.Drawing.Color.FromArgb(((int)(((byte)(26)))), ((int)(((byte)(35)))), ((int)(((byte)(126)))));
            this.pnlTopBar.Controls.Add(this.lblTitle);
            this.pnlTopBar.Controls.Add(this.btnClose);
            this.pnlTopBar.Dock = System.Windows.Forms.DockStyle.Top;
            this.pnlTopBar.Location = new System.Drawing.Point(0, 0);
            this.pnlTopBar.Name = "pnlTopBar";
            this.pnlTopBar.Size = new System.Drawing.Size(800, 70);
            this.pnlTopBar.TabIndex = 0;
            // 
            // lblTitle
            // 
            this.lblTitle.AutoSize = true;
            this.lblTitle.Font = new System.Drawing.Font("Segoe UI", 20F, System.Drawing.FontStyle.Bold);
            this.lblTitle.ForeColor = System.Drawing.Color.White;
            this.lblTitle.Location = new System.Drawing.Point(20, 18);
            this.lblTitle.Name = "lblTitle";
            this.lblTitle.Size = new System.Drawing.Size(350, 37);
            this.lblTitle.TabIndex = 0;
            this.lblTitle.Text = "🎟️ Barcode Sticker Print";
            // 
            // btnClose
            // 
            this.btnClose.Anchor = ((System.Windows.Forms.AnchorStyles)((System.Windows.Forms.AnchorStyles.Top | System.Windows.Forms.AnchorStyles.Right)));
            this.btnClose.BackColor = System.Drawing.Color.FromArgb(((int)(((byte)(244)))), ((int)(((byte)(67)))), ((int)(((byte)(54)))));
            this.btnClose.FlatAppearance.BorderSize = 0;
            this.btnClose.FlatStyle = System.Windows.Forms.FlatStyle.Flat;
            this.btnClose.Font = new System.Drawing.Font("Segoe UI", 10F);
            this.btnClose.ForeColor = System.Drawing.Color.White;
            this.btnClose.Location = new System.Drawing.Point(700, 20);
            this.btnClose.Name = "btnClose";
            this.btnClose.Size = new System.Drawing.Size(85, 35);
            this.btnClose.TabIndex = 1;
            this.btnClose.Text = "✕ Close";
            this.btnClose.UseVisualStyleBackColor = false;
            this.btnClose.Click += new System.EventHandler(this.btnClose_Click);
            // 
            // pnlMain
            // 
            this.pnlMain.BackColor = System.Drawing.Color.FromArgb(((int)(((byte)(250)))), ((int)(((byte)(250)))), ((int)(((byte)(250)))));
            this.pnlMain.Controls.Add(this.grpBasicInfo);
            this.pnlMain.Controls.Add(this.grpPricing);
            this.pnlMain.Controls.Add(this.pnlButtons);
            this.pnlMain.Dock = System.Windows.Forms.DockStyle.Fill;
            this.pnlMain.Location = new System.Drawing.Point(0, 70);
            this.pnlMain.Name = "pnlMain";
            this.pnlMain.Padding = new System.Windows.Forms.Padding(20);
            this.pnlMain.Size = new System.Drawing.Size(800, 480);
            this.pnlMain.TabIndex = 1;
            // 
            // grpBasicInfo
            // 
            this.grpBasicInfo.Controls.Add(this.txtDescription);
            this.grpBasicInfo.Controls.Add(this.lblDescription);
            this.grpBasicInfo.Controls.Add(this.cmbUnit);
            this.grpBasicInfo.Controls.Add(this.lblUnit);
            this.grpBasicInfo.Controls.Add(this.txtBarcode);
            this.grpBasicInfo.Controls.Add(this.lblBarcode);
            this.grpBasicInfo.Controls.Add(this.txtSKU);
            this.grpBasicInfo.Controls.Add(this.lblSKU);
            this.grpBasicInfo.Controls.Add(this.txtItemName);
            this.grpBasicInfo.Controls.Add(this.lblItemName);
            this.grpBasicInfo.Controls.Add(this.txtItemCode);
            this.grpBasicInfo.Controls.Add(this.lblItemCode);
            this.grpBasicInfo.Font = new System.Drawing.Font("Segoe UI", 10F, System.Drawing.FontStyle.Bold);
            this.grpBasicInfo.ForeColor = System.Drawing.Color.FromArgb(((int)(((byte)(52)))), ((int)(((byte)(73)))), ((int)(((byte)(94)))));
            this.grpBasicInfo.Location = new System.Drawing.Point(30, 30);
            this.grpBasicInfo.Name = "grpBasicInfo";
            this.grpBasicInfo.Size = new System.Drawing.Size(740, 220);
            this.grpBasicInfo.TabIndex = 0;
            this.grpBasicInfo.TabStop = false;
            this.grpBasicInfo.Text = "📝 Item Information";
            // 
            // txtDescription
            // 
            this.txtDescription.Font = new System.Drawing.Font("Segoe UI", 9F);
            this.txtDescription.Location = new System.Drawing.Point(120, 170);
            this.txtDescription.MaxLength = 500;
            this.txtDescription.Multiline = true;
            this.txtDescription.Name = "txtDescription";
            this.txtDescription.ScrollBars = System.Windows.Forms.ScrollBars.Vertical;
            this.txtDescription.Size = new System.Drawing.Size(600, 35);
            this.txtDescription.TabIndex = 11;
            // 
            // lblDescription
            // 
            this.lblDescription.AutoSize = true;
            this.lblDescription.Font = new System.Drawing.Font("Segoe UI", 9F);
            this.lblDescription.ForeColor = System.Drawing.Color.FromArgb(((int)(((byte)(52)))), ((int)(((byte)(73)))), ((int)(((byte)(94)))));
            this.lblDescription.Location = new System.Drawing.Point(20, 173);
            this.lblDescription.Name = "lblDescription";
            this.lblDescription.Size = new System.Drawing.Size(73, 15);
            this.lblDescription.TabIndex = 10;
            this.lblDescription.Text = "Description:";
            // 
            // cmbUnit
            // 
            this.cmbUnit.DropDownStyle = System.Windows.Forms.ComboBoxStyle.DropDownList;
            this.cmbUnit.Font = new System.Drawing.Font("Segoe UI", 9F);
            this.cmbUnit.FormattingEnabled = true;
            this.cmbUnit.Items.AddRange(new object[] {
            "PCS",
            "KG",
            "LITER",
            "METER",
            "BOX",
            "PACK"});
            this.cmbUnit.Location = new System.Drawing.Point(120, 140);
            this.cmbUnit.Name = "cmbUnit";
            this.cmbUnit.Size = new System.Drawing.Size(100, 23);
            this.cmbUnit.TabIndex = 9;
            // 
            // lblUnit
            // 
            this.lblUnit.AutoSize = true;
            this.lblUnit.Font = new System.Drawing.Font("Segoe UI", 9F);
            this.lblUnit.ForeColor = System.Drawing.Color.FromArgb(((int)(((byte)(52)))), ((int)(((byte)(73)))), ((int)(((byte)(94)))));
            this.lblUnit.Location = new System.Drawing.Point(20, 143);
            this.lblUnit.Name = "lblUnit";
            this.lblUnit.Size = new System.Drawing.Size(32, 15);
            this.lblUnit.TabIndex = 8;
            this.lblUnit.Text = "Unit:";
            // 
            // txtBarcode
            // 
            this.txtBarcode.Font = new System.Drawing.Font("Segoe UI", 9F);
            this.txtBarcode.Location = new System.Drawing.Point(540, 30);
            this.txtBarcode.MaxLength = 100;
            this.txtBarcode.Name = "txtBarcode";
            this.txtBarcode.Size = new System.Drawing.Size(180, 23);
            this.txtBarcode.TabIndex = 7;
            // 
            // lblBarcode
            // 
            this.lblBarcode.AutoSize = true;
            this.lblBarcode.Font = new System.Drawing.Font("Segoe UI", 9F);
            this.lblBarcode.ForeColor = System.Drawing.Color.FromArgb(((int)(((byte)(52)))), ((int)(((byte)(73)))), ((int)(((byte)(94)))));
            this.lblBarcode.Location = new System.Drawing.Point(480, 33);
            this.lblBarcode.Name = "lblBarcode";
            this.lblBarcode.Size = new System.Drawing.Size(54, 15);
            this.lblBarcode.TabIndex = 6;
            this.lblBarcode.Text = "Barcode:";
            // 
            // txtSKU
            // 
            this.txtSKU.Font = new System.Drawing.Font("Segoe UI", 9F);
            this.txtSKU.Location = new System.Drawing.Point(340, 30);
            this.txtSKU.MaxLength = 50;
            this.txtSKU.Name = "txtSKU";
            this.txtSKU.Size = new System.Drawing.Size(120, 23);
            this.txtSKU.TabIndex = 5;
            // 
            // lblSKU
            // 
            this.lblSKU.AutoSize = true;
            this.lblSKU.Font = new System.Drawing.Font("Segoe UI", 9F);
            this.lblSKU.ForeColor = System.Drawing.Color.FromArgb(((int)(((byte)(52)))), ((int)(((byte)(73)))), ((int)(((byte)(94)))));
            this.lblSKU.Location = new System.Drawing.Point(300, 33);
            this.lblSKU.Name = "lblSKU";
            this.lblSKU.Size = new System.Drawing.Size(31, 15);
            this.lblSKU.TabIndex = 4;
            this.lblSKU.Text = "SKU:";
            // 
            // txtItemName
            // 
            this.txtItemName.Font = new System.Drawing.Font("Segoe UI", 9F);
            this.txtItemName.Location = new System.Drawing.Point(120, 60);
            this.txtItemName.MaxLength = 100;
            this.txtItemName.Name = "txtItemName";
            this.txtItemName.Size = new System.Drawing.Size(600, 23);
            this.txtItemName.TabIndex = 3;
            // 
            // lblItemName
            // 
            this.lblItemName.AutoSize = true;
            this.lblItemName.Font = new System.Drawing.Font("Segoe UI", 9F);
            this.lblItemName.ForeColor = System.Drawing.Color.FromArgb(((int)(((byte)(52)))), ((int)(((byte)(73)))), ((int)(((byte)(94)))));
            this.lblItemName.Location = new System.Drawing.Point(20, 63);
            this.lblItemName.Name = "lblItemName";
            this.lblItemName.Size = new System.Drawing.Size(69, 15);
            this.lblItemName.TabIndex = 2;
            this.lblItemName.Text = "Item Name:";
            // 
            // txtItemCode
            // 
            this.txtItemCode.Font = new System.Drawing.Font("Segoe UI", 9F);
            this.txtItemCode.Location = new System.Drawing.Point(120, 30);
            this.txtItemCode.MaxLength = 50;
            this.txtItemCode.Name = "txtItemCode";
            this.txtItemCode.Size = new System.Drawing.Size(150, 23);
            this.txtItemCode.TabIndex = 1;
            // 
            // lblItemCode
            // 
            this.lblItemCode.AutoSize = true;
            this.lblItemCode.Font = new System.Drawing.Font("Segoe UI", 9F);
            this.lblItemCode.ForeColor = System.Drawing.Color.FromArgb(((int)(((byte)(52)))), ((int)(((byte)(73)))), ((int)(((byte)(94)))));
            this.lblItemCode.Location = new System.Drawing.Point(20, 33);
            this.lblItemCode.Name = "lblItemCode";
            this.lblItemCode.Size = new System.Drawing.Size(65, 15);
            this.lblItemCode.TabIndex = 0;
            this.lblItemCode.Text = "Item Code:";
            // 
            // grpPricing
            // 
            this.grpPricing.Controls.Add(this.txtSalesPrice);
            this.grpPricing.Controls.Add(this.lblSalesPrice);
            this.grpPricing.Controls.Add(this.txtMRP);
            this.grpPricing.Controls.Add(this.lblMRP);
            this.grpPricing.Font = new System.Drawing.Font("Segoe UI", 10F, System.Drawing.FontStyle.Bold);
            this.grpPricing.ForeColor = System.Drawing.Color.FromArgb(((int)(((byte)(52)))), ((int)(((byte)(73)))), ((int)(((byte)(94)))));
            this.grpPricing.Location = new System.Drawing.Point(30, 260);
            this.grpPricing.Name = "grpPricing";
            this.grpPricing.Size = new System.Drawing.Size(740, 80);
            this.grpPricing.TabIndex = 1;
            this.grpPricing.TabStop = false;
            this.grpPricing.Text = "💰 Pricing Information";
            // 
            // txtSalesPrice
            // 
            this.txtSalesPrice.Font = new System.Drawing.Font("Segoe UI", 9F);
            this.txtSalesPrice.Location = new System.Drawing.Point(350, 35);
            this.txtSalesPrice.Name = "txtSalesPrice";
            this.txtSalesPrice.Size = new System.Drawing.Size(100, 23);
            this.txtSalesPrice.TabIndex = 3;
            this.txtSalesPrice.Text = "0.00";
            this.txtSalesPrice.TextAlign = System.Windows.Forms.HorizontalAlignment.Right;
            this.txtSalesPrice.Leave += new System.EventHandler(this.txtSalesPrice_Leave);
            this.txtSalesPrice.KeyPress += new System.Windows.Forms.KeyPressEventHandler(this.NumericField_KeyPress);
            // 
            // lblSalesPrice
            // 
            this.lblSalesPrice.AutoSize = true;
            this.lblSalesPrice.Font = new System.Drawing.Font("Segoe UI", 9F);
            this.lblSalesPrice.ForeColor = System.Drawing.Color.FromArgb(((int)(((byte)(52)))), ((int)(((byte)(73)))), ((int)(((byte)(94)))));
            this.lblSalesPrice.Location = new System.Drawing.Point(260, 38);
            this.lblSalesPrice.Name = "lblSalesPrice";
            this.lblSalesPrice.Size = new System.Drawing.Size(84, 15);
            this.lblSalesPrice.TabIndex = 2;
            this.lblSalesPrice.Text = "Trend Price (₹):";
            // 
            // txtMRP
            // 
            this.txtMRP.Font = new System.Drawing.Font("Segoe UI", 9F);
            this.txtMRP.Location = new System.Drawing.Point(120, 35);
            this.txtMRP.Name = "txtMRP";
            this.txtMRP.Size = new System.Drawing.Size(100, 23);
            this.txtMRP.TabIndex = 1;
            this.txtMRP.Text = "0.00";
            this.txtMRP.TextAlign = System.Windows.Forms.HorizontalAlignment.Right;
            this.txtMRP.Leave += new System.EventHandler(this.txtMRP_Leave);
            this.txtMRP.KeyPress += new System.Windows.Forms.KeyPressEventHandler(this.NumericField_KeyPress);
            // 
            // lblMRP
            // 
            this.lblMRP.AutoSize = true;
            this.lblMRP.Font = new System.Drawing.Font("Segoe UI", 9F);
            this.lblMRP.ForeColor = System.Drawing.Color.FromArgb(((int)(((byte)(52)))), ((int)(((byte)(73)))), ((int)(((byte)(94)))));
            this.lblMRP.Location = new System.Drawing.Point(20, 38);
            this.lblMRP.Name = "lblMRP";
            this.lblMRP.Size = new System.Drawing.Size(52, 15);
            this.lblMRP.TabIndex = 0;
            this.lblMRP.Text = "MRP (₹):";
            // 
            // pnlButtons
            // 
            this.pnlButtons.Controls.Add(this.btnPrintSticker);
            this.pnlButtons.Controls.Add(this.btnClear);
            this.pnlButtons.Dock = System.Windows.Forms.DockStyle.Bottom;
            this.pnlButtons.Location = new System.Drawing.Point(20, 360);
            this.pnlButtons.Name = "pnlButtons";
            this.pnlButtons.Size = new System.Drawing.Size(760, 100);
            this.pnlButtons.TabIndex = 2;
            // 
            // btnPrintSticker
            // 
            this.btnPrintSticker.BackColor = System.Drawing.Color.FromArgb(((int)(((byte)(0)))), ((int)(((byte)(150)))), ((int)(((byte)(136)))));
            this.btnPrintSticker.FlatAppearance.BorderSize = 0;
            this.btnPrintSticker.FlatStyle = System.Windows.Forms.FlatStyle.Flat;
            this.btnPrintSticker.Font = new System.Drawing.Font("Segoe UI", 12F, System.Drawing.FontStyle.Bold);
            this.btnPrintSticker.ForeColor = System.Drawing.Color.White;
            this.btnPrintSticker.Location = new System.Drawing.Point(530, 20);
            this.btnPrintSticker.Name = "btnPrintSticker";
            this.btnPrintSticker.Size = new System.Drawing.Size(200, 50);
            this.btnPrintSticker.TabIndex = 0;
            this.btnPrintSticker.Text = "🎟️ Print Sticker";
            this.btnPrintSticker.UseVisualStyleBackColor = false;
            this.btnPrintSticker.Click += new System.EventHandler(this.btnPrintSticker_Click);
            // 
            // btnClear
            // 
            this.btnClear.BackColor = System.Drawing.Color.FromArgb(((int)(((byte)(255)))), ((int)(((byte)(152)))), ((int)(((byte)(0)))));
            this.btnClear.FlatAppearance.BorderSize = 0;
            this.btnClear.FlatStyle = System.Windows.Forms.FlatStyle.Flat;
            this.btnClear.Font = new System.Drawing.Font("Segoe UI", 12F, System.Drawing.FontStyle.Bold);
            this.btnClear.ForeColor = System.Drawing.Color.White;
            this.btnClear.Location = new System.Drawing.Point(310, 20);
            this.btnClear.Name = "btnClear";
            this.btnClear.Size = new System.Drawing.Size(200, 50);
            this.btnClear.TabIndex = 1;
            this.btnClear.Text = "🔄 Clear";
            this.btnClear.UseVisualStyleBackColor = false;
            this.btnClear.Click += new System.EventHandler(this.btnClear_Click);
            // 
            // BarcodeStickerForm
            // 
            this.AutoScaleDimensions = new System.Drawing.SizeF(7F, 15F);
            this.AutoScaleMode = System.Windows.Forms.AutoScaleMode.Font;
            this.BackColor = System.Drawing.Color.FromArgb(((int)(((byte)(250)))), ((int)(((byte)(250)))), ((int)(((byte)(250)))));
            this.ClientSize = new System.Drawing.Size(800, 550);
            this.Controls.Add(this.pnlMain);
            this.Controls.Add(this.pnlTopBar);
            this.Font = new System.Drawing.Font("Segoe UI", 9F);
            this.FormBorderStyle = System.Windows.Forms.FormBorderStyle.FixedSingle;
            this.MaximizeBox = false;
            this.MinimizeBox = false;
            this.Name = "BarcodeStickerForm";
            this.StartPosition = System.Windows.Forms.FormStartPosition.CenterScreen;
            this.Text = "UZRS - Barcode Sticker Print";
            this.Load += new System.EventHandler(this.BarcodeStickerForm_Load);
            this.pnlTopBar.ResumeLayout(false);
            this.pnlTopBar.PerformLayout();
            this.pnlMain.ResumeLayout(false);
            this.grpBasicInfo.ResumeLayout(false);
            this.grpBasicInfo.PerformLayout();
            this.grpPricing.ResumeLayout(false);
            this.grpPricing.PerformLayout();
            this.pnlButtons.ResumeLayout(false);
            this.ResumeLayout(false);

        }

        #endregion

        private Panel pnlTopBar;
        private Label lblTitle;
        private Button btnClose;
        private Panel pnlMain;
        private GroupBox grpBasicInfo;
        private TextBox txtDescription;
        private Label lblDescription;
        private ComboBox cmbUnit;
        private Label lblUnit;
        private TextBox txtBarcode;
        private Label lblBarcode;
        private TextBox txtSKU;
        private Label lblSKU;
        private TextBox txtItemName;
        private Label lblItemName;
        private TextBox txtItemCode;
        private Label lblItemCode;
        private GroupBox grpPricing;
        private TextBox txtSalesPrice;
        private Label lblSalesPrice;
        private TextBox txtMRP;
        private Label lblMRP;
        private Panel pnlButtons;
        private Button btnPrintSticker;
        private Button btnClear;
    }
}
