using System.Drawing;
using System.Windows.Forms;

namespace UzrsInventory;

partial class MainForm
{
    /// <summary>
    ///  Required designer variable.
    /// </summary>
    private System.ComponentModel.IContainer components = null;

    private StatusStrip statusStrip = null!;
    private ToolStripStatusLabel lblStatus = null!;
    private ToolStripStatusLabel lblConnection = null!;

    /// <summary>
    ///  Clean up any resources being used.
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

    private void InitializeComponent()
    {
        components = new System.ComponentModel.Container();
        statusStrip = new StatusStrip();
        lblStatus = new ToolStripStatusLabel();
        lblConnection = new ToolStripStatusLabel();
        statusStrip.SuspendLayout();
        SuspendLayout();
        // 
        // statusStrip
        // 
        statusStrip.ImageScalingSize = new Size(20, 20);
        statusStrip.Items.AddRange(new ToolStripItem[] { lblStatus, lblConnection });
        statusStrip.Location = new Point(0, 770);
        statusStrip.Name = "statusStrip";
        statusStrip.Padding = new Padding(1, 0, 19, 0);
        statusStrip.Size = new Size(1200, 30);
        statusStrip.TabIndex = 1;
        statusStrip.Text = "statusStrip";
        // 
        // lblStatus
        // 
        lblStatus.Name = "lblStatus";
        lblStatus.Padding = new Padding(0, 0, 10, 0);
        lblStatus.Size = new Size(187, 24);
        lblStatus.Text = "Initializing web view...";
        // 
        // lblConnection
        // 
        lblConnection.BorderSides = ToolStripStatusLabelBorderSides.Left | ToolStripStatusLabelBorderSides.Top | ToolStripStatusLabelBorderSides.Bottom;
        lblConnection.ForeColor = Color.DarkOrange;
        lblConnection.Margin = new Padding(5, 3, 0, 2);
        lblConnection.Name = "lblConnection";
        lblConnection.Padding = new Padding(10, 0, 0, 0);
        lblConnection.Size = new Size(108, 25);
        lblConnection.Text = "Offline";
        // 
        // MainForm
        // 
        AutoScaleDimensions = new SizeF(8F, 20F);
        AutoScaleMode = AutoScaleMode.Font;
        ClientSize = new Size(1200, 800);
        Controls.Add(statusStrip);
        Margin = new Padding(4);
        MinimumSize = new Size(800, 600);
        Name = "MainForm";
        StartPosition = FormStartPosition.CenterScreen;
        Text = "UZRS Inventory";
        WindowState = FormWindowState.Maximized;
        Load += MainForm_Load;
        statusStrip.ResumeLayout(false);
        statusStrip.PerformLayout();
        ResumeLayout(false);
        PerformLayout();
    }

    #endregion
}
