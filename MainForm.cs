using Microsoft.Web.WebView2.Core;
using Microsoft.Web.WebView2.WinForms;
using System.Collections.Generic;
using System.Diagnostics;
using System.Drawing;
using System.Drawing.Printing;
using System.IO;
using System.Net.Http;
using System.Net.NetworkInformation;
using System.Text;
using System.Text.Json;
using UzrsInventory.BarcodeSticker;
using System.Windows.Forms;
using System;
using System.Threading.Tasks;

namespace UzrsInventory;

public partial class MainForm : Form
{
    private static readonly HttpClient HttpClientInstance = CreateHttpClient();
    // 1. Change URL to localhost
    // homeUri replaced by homeUri_Dynamic
    private readonly string userDataFolder = Path.Combine(Environment.GetFolderPath(Environment.SpecialFolder.LocalApplicationData), "UZRSInventoryBrowser");
    private readonly string printerConfigFile = Path.Combine(Environment.GetFolderPath(Environment.SpecialFolder.LocalApplicationData), "UZRSInventoryBrowser", "printer_config.txt");
    private readonly string urlConfigFile = Path.Combine(Environment.GetFolderPath(Environment.SpecialFolder.LocalApplicationData), "UZRSInventoryBrowser", "url_config.txt");
    
    private WebView2 webView;
    private System.Windows.Forms.Timer networkMonitor;
    private bool pendingRetry;
    private bool lastKnownOnline;
    private string selectedPrinter = "";
    private Uri homeUri_Dynamic; // Use this instead of readonly homeUri

    private readonly HashSet<CoreWebView2WebErrorStatus> transientErrors = new()
    {
        CoreWebView2WebErrorStatus.ConnectionAborted,
        CoreWebView2WebErrorStatus.ConnectionReset,
        CoreWebView2WebErrorStatus.Timeout,
        CoreWebView2WebErrorStatus.Disconnected,
        CoreWebView2WebErrorStatus.HostNameNotResolved,
        CoreWebView2WebErrorStatus.CannotConnect
    };

    public MainForm()
    {
        InitializeComponent();
        Text = "UZRS Inventory v1.1";
        CheckConfig();
    }

    private void CheckConfig()
    {
        try 
        {
            Directory.CreateDirectory(Path.GetDirectoryName(printerConfigFile));

            // Printer Config
            if (File.Exists(printerConfigFile))
            {
                selectedPrinter = File.ReadAllText(printerConfigFile).Trim();
            }

            // URL Config
            string defaultUrl = "http://localhost/uzrs_moi_vvs/index.php"; // Default fallback
            if (File.Exists(urlConfigFile))
            {
                string configUrl = File.ReadAllText(urlConfigFile).Trim();
                if (Uri.TryCreate(configUrl, UriKind.Absolute, out Uri result))
                {
                    homeUri_Dynamic = result;
                }
                else
                {
                   homeUri_Dynamic = new Uri(defaultUrl);
                }
            }
            else
            {
                homeUri_Dynamic = new Uri(defaultUrl);
                // Optional: Create the file with default so user knows where to change it
                try { File.WriteAllText(urlConfigFile, defaultUrl); } catch {}
            }

            // Always ask for printer on startup
            PromptForPrinterSelection(selectedPrinter);
        }
        catch (Exception ex)
        {
            Debug.WriteLine("Error reading config: " + ex.Message);
            homeUri_Dynamic = new Uri("http://localhost/uzrs_moi_vvs/index.php");
        }
    }

    private void PromptForPrinterSelection(string defaultPrinter = null)
    {
        using (var form = new PrinterSelectionForm(defaultPrinter))
        {
            if (form.ShowDialog() == DialogResult.OK)
            {
                selectedPrinter = form.SelectedPrinter;
                try
                {
                    File.WriteAllText(printerConfigFile, selectedPrinter);
                    MessageBox.Show($"Printer set to: {selectedPrinter}", "Configuration Saved", MessageBoxButtons.OK, MessageBoxIcon.Information);
                }
                catch (Exception ex)
                {
                    MessageBox.Show($"Error saving printer config: {ex.Message}");
                }
            }
        }
    }

    private static HttpClient CreateHttpClient()
    {
        var client = new HttpClient { Timeout = TimeSpan.FromSeconds(5) };
        client.DefaultRequestHeaders.UserAgent.ParseAdd("UZRSInventoryDesktop/1.0");
        return client;
    }

    private async void MainForm_Load(object sender, EventArgs e)
    {
        lblStatus.Text = "Bootstrapping secure browser...";
        try
        {
            await InitializeWebViewAsync();
        }
        catch (Exception ex)
        {
            lblStatus.Text = "Browser initialization failed";
            var initError = $"Unable to initialize the embedded browser.{Environment.NewLine}{ex.Message}";
            MessageBox.Show(this, initError, "Initialization Error", MessageBoxButtons.OK, MessageBoxIcon.Error);
        }

        SetupNetworkMonitoring();
    }

    private async Task InitializeWebViewAsync()
    {
        Directory.CreateDirectory(userDataFolder);

        webView = new WebView2
        {
            Dock = DockStyle.Fill,
            AllowExternalDrop = false
        };

        Controls.Add(webView);
        webView.SendToBack();
        statusStrip.BringToFront();

        var envOptions = new CoreWebView2EnvironmentOptions
        {
            AdditionalBrowserArguments = "--disable-features=msSmartScreenProtection"
        };

        var environment = await CoreWebView2Environment.CreateAsync(null, userDataFolder, envOptions);
        await webView.EnsureCoreWebView2Async(environment);

        webView.NavigationStarting += WebView_NavigationStarting;
        webView.NavigationCompleted += WebView_NavigationCompleted;

        ConfigureWebView(webView.CoreWebView2);
        lblStatus.Text = "Signed-in session ready";
        webView.CoreWebView2.Navigate(homeUri_Dynamic.AbsoluteUri);
    }

    private void ConfigureWebView(CoreWebView2 core)
    {
        var settings = core.Settings;
        settings.AreDefaultContextMenusEnabled = true;
        settings.AreDevToolsEnabled = false;
        settings.IsStatusBarEnabled = false;
        settings.IsZoomControlEnabled = true;
        settings.IsPasswordAutosaveEnabled = true;
        settings.IsGeneralAutofillEnabled = true;

        core.ProcessFailed += Core_ProcessFailed;
        core.HistoryChanged += Core_HistoryChanged;
        core.WebMessageReceived += Core_WebMessageReceived;
    }

    private void Core_ProcessFailed(object sender, CoreWebView2ProcessFailedEventArgs e)
    {
        if (!IsHandleCreated)
        {
            return;
        }

        BeginInvoke(new Action(() =>
        {
            lblStatus.Text = "Browser process restarted, refreshing...";
            TryReloadCurrentPage();
        }));
    }

    private void Core_HistoryChanged(object sender, object e)
    {
        // Navigation buttons removed for clean native experience
    }

    private void SetupNetworkMonitoring()
    {
        UpdateConnectivityUi(NetworkInterface.GetIsNetworkAvailable());
        NetworkChange.NetworkAvailabilityChanged += NetworkAvailabilityChanged;

        networkMonitor = new System.Windows.Forms.Timer
        {
            Interval = 15_000
        };
        networkMonitor.Tick += NetworkMonitor_Tick;
        networkMonitor.Start();
    }

    private void NetworkAvailabilityChanged(object sender, NetworkAvailabilityEventArgs e)
    {
        if (!IsHandleCreated)
        {
            return;
        }

        BeginInvoke(new Action(() =>
        {
            UpdateConnectivityUi(e.IsAvailable);
            if (e.IsAvailable && pendingRetry)
            {
                pendingRetry = false;
                TryReloadCurrentPage();
            }
        }));
    }

    private async void NetworkMonitor_Tick(object sender, EventArgs e)
    {
        var reachable = await CheckInternetReachabilityAsync();
        UpdateConnectivityUi(reachable);
        if (reachable && pendingRetry)
        {
            pendingRetry = false;
            TryReloadCurrentPage();
        }
    }

    private async Task<bool> CheckInternetReachabilityAsync()
    {
        if (!NetworkInterface.GetIsNetworkAvailable())
        {
            return false;
        }

        try
        {
            using var request = new HttpRequestMessage(HttpMethod.Head, homeUri_Dynamic);
            using var response = await HttpClientInstance.SendAsync(request, HttpCompletionOption.ResponseHeadersRead);
            return response.IsSuccessStatusCode;
        }
        catch (TaskCanceledException)
        {
            return false;
        }
        catch (HttpRequestException)
        {
            return false;
        }
    }

    private void UpdateConnectivityUi(bool isOnline)
    {
        lastKnownOnline = isOnline;
        lblConnection.Text = isOnline ? "Online" : "Offline";
        lblConnection.ForeColor = isOnline ? Color.ForestGreen : Color.DarkOrange;
        if (!isOnline)
        {
            lblStatus.Text = "Waiting for a stable internet connection...";
        }
    }

    private void WebView_NavigationStarting(object sender, CoreWebView2NavigationStartingEventArgs e)
    {
        string hostName;
        try
        {
            hostName = new Uri(e.Uri).Host;
        }
        catch (UriFormatException)
        {
            hostName = e.Uri;
        }

        Text = $"UZRS Inventory - Loading {hostName}";
        lblStatus.Text = "Loading...";
    }

    private void WebView_NavigationCompleted(object sender, CoreWebView2NavigationCompletedEventArgs e)
    {
        if (e.IsSuccess)
        {
            Text = "UZRS Inventory";
            lblStatus.Text = "Connected";
            pendingRetry = false;
        }
        else
        {
            Text = "UZRS Inventory - Error";
            lblStatus.Text = $"Connection problem ({e.WebErrorStatus})";
            HandleNavigationFailure(e);
        }
    }

    private void HandleNavigationFailure(CoreWebView2NavigationCompletedEventArgs e)
    {
        if (!transientErrors.Contains(e.WebErrorStatus))
        {
            return;
        }

        if (!pendingRetry)
        {
            pendingRetry = true;
            lblStatus.Text += " - Retrying shortly";
            _ = Task.Run(async () =>
            {
                await Task.Delay(3000).ConfigureAwait(false);
                if (!IsHandleCreated)
                {
                    return;
                }

                BeginInvoke(new Action(() =>
                {
                    pendingRetry = false;
                    TryReloadCurrentPage();
                }));
            });
        }
    }

    private void TryReloadCurrentPage()
    {
        if (webView?.CoreWebView2 == null)
        {
            return;
        }

        try
        {
            if (lastKnownOnline)
            {
                webView.Reload();
            }
            else
            {
                lblStatus.Text = "Waiting for connectivity before reloading...";
            }
        }
        catch (Exception)
        {
            lblStatus.Text = "Reload failed - navigating home";
            webView.CoreWebView2.Navigate(homeUri_Dynamic.AbsoluteUri);
        }
    }

    private void Core_WebMessageReceived(object sender, CoreWebView2WebMessageReceivedEventArgs e)
    {
        try
        {
            var messageJson = e.WebMessageAsJson;
            if (string.IsNullOrEmpty(messageJson))
            {
                return;
            }

            var options = new JsonSerializerOptions
            {
                PropertyNameCaseInsensitive = true,
                AllowTrailingCommas = true
            };

            var message = JsonSerializer.Deserialize<Dictionary<string, JsonElement>>(messageJson, options);
            if (message == null || !message.ContainsKey("action"))
            {
                return;
            }

            var action = message["action"].GetString();
            Debug.WriteLine($"Received action: {action}");

            if (action == "printBarcode")
            {
                HandlePrintBarcodeRequest(message);
            }
            else if (action == "printCartStickers")
            {
                HandlePrintCartStickerRequest(message);
            }
            else if (action == "printReceipt")
            {
                HandlePrintReceiptRequest(message);
            }
            else if (action == "configurePrinter")
            {
                PromptForPrinterSelection(selectedPrinter);
            }
            else if (action == "printDenomSummary")
            {
                Debug.WriteLine("Handling denomination summary print request");
                HandlePrintDenomSummaryRequest(message);
            }
            else
            {
                Debug.WriteLine($"Unknown action: {action}");
            }
        }
        catch (Exception ex)
        {
            Debug.WriteLine($"WebMessageReceived Error: {ex.Message}\n{ex.StackTrace}");
            MessageBox.Show($"Error processing message: {ex.Message}\n\nStack Trace:\n{ex.StackTrace}", "WebView Error", MessageBoxButtons.OK, MessageBoxIcon.Error);
        }
    }

    // State for printing
    private JsonElement currentReceiptData;
    private JsonElement currentMeta;
    private bool printingCounterCopy = false; // Added for 2nd receipt support

    private void HandlePrintReceiptRequest(Dictionary<string, JsonElement> data)
    {
        if (string.IsNullOrEmpty(selectedPrinter))
        {
            MessageBox.Show("No printer configured! Please configure printer.", "Print Error", MessageBoxButtons.OK, MessageBoxIcon.Error);
            PromptForPrinterSelection(selectedPrinter);
            if (string.IsNullOrEmpty(selectedPrinter)) return;
        }

        try 
        {
            // Store data for the PrintPage event
            currentReceiptData = data["data"];
            if (data.TryGetValue("meta", out var meta))
            {
                currentMeta = meta;
            }
            
            // Reset state
            printingCounterCopy = false;

            // Print Customer Copy
            printingCounterCopy = false;
            using (PrintDocument pd1 = new PrintDocument())
            {
                pd1.PrinterSettings.PrinterName = selectedPrinter;
                pd1.PrintPage += new PrintPageEventHandler(this.PrintReceiptPage);
                pd1.Print();
            }

            // Print Counter Copy (separate job to force cut)
            printingCounterCopy = true;
            using (PrintDocument pd2 = new PrintDocument())
            {
                pd2.PrinterSettings.PrinterName = selectedPrinter;
                pd2.PrintPage += new PrintPageEventHandler(this.PrintReceiptPage);
                pd2.Print();
            }
        }
        catch (Exception ex)
        {
             MessageBox.Show($"Error printing receipt: {ex.Message}", "Print Error", MessageBoxButtons.OK, MessageBoxIcon.Error);
        }
    }

    private void PrintReceiptPage(object sender, PrintPageEventArgs e)
    {
        Graphics g = e.Graphics;
        float width = e.PageSettings.PrintableArea.Width;
        float y = 0;
        float leftMargin = 0;
        float rightMargin = width;

        // --- FONTS ---
        Font fontTamilSmall = new Font("Nirmala UI", 7, FontStyle.Regular);
        Font fontTamilRegular = new Font("Nirmala UI", 9, FontStyle.Bold);
        Font fontTamilMedium = new Font("Nirmala UI", 10, FontStyle.Bold);
        Font fontTamilLarge = new Font("Nirmala UI", 12, FontStyle.Bold);
        Font fontBanner = new Font("Nirmala UI", 16, FontStyle.Bold);
        
        Font fontEngSmall = new Font("Arial", 8, FontStyle.Regular);
        Font fontEngMedium = new Font("Arial", 12, FontStyle.Bold); // Increased from 10 to 12
        Font fontEngLarge = new Font("Arial", 11, FontStyle.Bold);
        
        // Compact fonts for counter copy
        Font fontCompactRegular = new Font("Nirmala UI", 8, FontStyle.Regular);
        Font fontCompactBold = new Font("Nirmala UI", 8, FontStyle.Bold);
        Font fontCompactMedium = new Font("Nirmala UI", 9, FontStyle.Bold);
        Font fontCompactLarge = new Font("Nirmala UI", 11, FontStyle.Bold);

        // Specific fonts for sections
        Font fontBillMeta = new Font("Courier New", 9, FontStyle.Bold); 
        Font fontAmountLabel = new Font("Nirmala UI", 16, FontStyle.Bold);
        Font fontAmountValue = new Font("Arial", 16, FontStyle.Bold);
        
        StringFormat centerAlign = new StringFormat { Alignment = StringAlignment.Center };
        StringFormat leftAlign = new StringFormat { Alignment = StringAlignment.Near };
        StringFormat rightAlign = new StringFormat { Alignment = StringAlignment.Far };

        // --- DATA EXTRACTION ---
        JsonElement item = currentReceiptData;
        if (item.ValueKind == JsonValueKind.Array)
        {
            foreach (var i in item.EnumerateArray()) { item = i; break; }
        }

        string GetStr(string key) 
        {
            if (!item.TryGetProperty(key, out var val)) return "";
            if (val.ValueKind == JsonValueKind.Number) return val.ToString();
            if (val.ValueKind == JsonValueKind.String) return val.GetString() ?? "";
            return val.ToString();
        }
        
        string billNo = GetStr("billNumberPrint");
        if(string.IsNullOrEmpty(billNo)) billNo = GetStr("id");
        
        string compNo = GetStr("computerNumberPrint");
        if(string.IsNullOrEmpty(compNo)) compNo = "001";
        
        string dateStr = DateTime.Now.ToString("dd MMM yy h:mm tt");
        
        string functionName = "";
        if (currentMeta.ValueKind == JsonValueKind.Object)
        {
             if (currentMeta.TryGetProperty("functionName", out var fn)) functionName = fn.GetString() ?? "";
        }
        if(string.IsNullOrEmpty(functionName)) functionName = "விசேஷ விவரங்கள்";

        string location = GetStr("location");
        string village = GetStr("villageGoingTo");
        string initial = GetStr("initial");
        string initial2 = GetStr("initial2");
        string name1 = GetStr("name1");
        string name2 = GetStr("name2");
        string occupation = GetStr("occupation");
        string occupation2 = GetStr("occupation2");
        string phone = GetStr("phone");
        string description = GetStr("description");
        string customerNo = GetStr("customerNumber");
        string relationship = GetStr("relationship");
        string amount = GetStr("total_amount");
        
        // Format Amount
        string formattedAmount = amount;
        if (decimal.TryParse(amount, out decimal amountDec))
        {
            try {
                System.Globalization.CultureInfo indian = new System.Globalization.CultureInfo("en-IN");
                formattedAmount = amountDec.ToString("N2", indian); 
            } catch {
                formattedAmount = amountDec.ToString("N2");
            }
        }
        else 
        {
            if (!string.IsNullOrEmpty(formattedAmount)) formattedAmount += ".00";
        }

        // Helper to draw text that wraps and returns the height used
        float DrawWrappedText(string text, Font font, Brush brush, float x, float yPos, float w, StringFormat align)
        {
            if (string.IsNullOrEmpty(text)) return 0;
            RectangleF rect = new RectangleF(x, yPos, w, 1000); 
            SizeF size = g.MeasureString(text, font, (int)w, align);
            g.DrawString(text, font, brush, rect, align);
            return size.Height;
        }

        // --- DRAWING LOGIC ---

        if (!printingCounterCopy)
        {
            // === CUSTOMER COPY (FULL DESIGN) ===

            // 1. TOP HEADER (Tamil)
            g.DrawString("உ", fontTamilMedium, Brushes.Black, width/2, y, centerAlign); 
            y += 18;
            g.DrawString("தும்மக்குண்டு ஸ்ரீ வைரவ சாமி துணை", fontTamilSmall, Brushes.Black, width/2, y, centerAlign); 
            y += 14;
            g.DrawString("நாட்டாமங்கலம் ஸ்ரீ ஆதிசிவன் துணை", fontTamilSmall, Brushes.Black, width/2, y, centerAlign); 
            y += 18;

            // 2. BLACK BANNER (VVS மொய் டெக்)
            float bannerHeight = 35;
            g.FillRectangle(Brushes.Black, 0, y, width, bannerHeight);
            g.DrawString("VVS மொய் டெக்", fontBanner, Brushes.White, width/2, y + 2, centerAlign);
            y += bannerHeight + 5;

            // 3. LOGO & PHONES (Side by Side)
            float logoWidth = 90; 
            float logoHeight = 90;
            float logoX = 5;
            
            float phonesW = 140; // Increased width for larger font
            float phonesX = width - phonesW - 5; 
            
            // Logo
            string logoPath = Path.Combine(Application.StartupPath, "Properties", "vvs_logo.jpg");
            if (!File.Exists(logoPath)) logoPath = Path.Combine(AppDomain.CurrentDomain.BaseDirectory, "Properties", "vvs_logo.jpg");
            if (!File.Exists(logoPath)) logoPath = Path.Combine(Application.StartupPath, "vvs_logo.jpg");

            if (File.Exists(logoPath))
            {
                try {
                    using(Image img = Image.FromFile(logoPath))
                    {
                        g.DrawImage(img, logoX, y, logoWidth, logoHeight);
                    }
                } catch {}
            }
            else 
            {
                g.DrawString("[LOGO]", fontEngSmall, Brushes.Black, logoX, y + 20);
            }

            // Phones
            float phY = y + 5;
            g.DrawString("915 925 925 0", fontEngMedium, Brushes.Black, phonesX, phY); phY += 22;
            g.DrawString("915 925 925 1", fontEngMedium, Brushes.Black, phonesX, phY); phY += 22;
            g.DrawString("915 925 925 2", fontEngMedium, Brushes.Black, phonesX, phY);
            
            y = Math.Max(y + logoHeight, phY + 10) + 10;

            // 4. SEPARATOR (Dashed)
            DrawDashedLine(g, y, width);
            y += 5;

            // 5. BILL INFO LINE 1
            g.DrawString($"எண் :{billNo}", fontTamilMedium, Brushes.Black, leftMargin, y, leftAlign);
            g.DrawString(dateStr, fontBillMeta, Brushes.Black, rightMargin, y, rightAlign);
            y += 18;
            
            // Solid Line
            DrawSolidLine(g, y, width);
            y += 5;

            // 6. BILL INFO LINE 2
            g.DrawString($"கணினி: {compNo}", fontTamilMedium, Brushes.Black, leftMargin, y, leftAlign);
            g.DrawString("விசேஷ விவரங்கள்", fontTamilMedium, Brushes.Black, rightMargin, y, rightAlign);
            y += 20;

            // 7. FUNCTION NAME BANNER (Gray)
            SizeF fnSize = g.MeasureString(functionName, fontTamilLarge, (int)width, centerAlign);
            float fnBannerH = Math.Max(30, fnSize.Height + 8);
            g.FillRectangle(Brushes.LightGray, 0, y, width, fnBannerH);
            DrawWrappedText(functionName, fontTamilLarge, Brushes.Black, 0, y + 4, width, centerAlign);
            y += fnBannerH;
            
            // Solid Line
            DrawSolidLine(g, y, width);
            y += 5;

            // 8. FUNCTION META (Date Place)
            if (currentMeta.ValueKind == JsonValueKind.Object && currentMeta.TryGetProperty("functionMeta", out var rawMeta))
            {
                string rm = rawMeta.GetString() ?? "";
                float metaH = DrawWrappedText(rm, fontTamilRegular, Brushes.Black, 0, y, width, centerAlign);
                y += metaH;
            }
            else
            {
                g.DrawString("29-11-2025   AAA Mahal", fontTamilRegular, Brushes.Black, width/2, y, centerAlign);
                y += 20;
            }

            g.DrawString("by", new Font("Arial", 8, FontStyle.Italic), Brushes.Gray, width/2, y, centerAlign);
            y += 15;

            // 9. SEPARATOR (Dashed)
            DrawDashedLine(g, y, width);
            y += 5;

            // 10. GIVER DETAILS
            // Village / Location info
            string displayVillage = !string.IsNullOrEmpty(village) ? village : location;
            if (!string.IsNullOrEmpty(displayVillage)) {
                string locInfo = displayVillage;
                if (!string.IsNullOrEmpty(location) && location != displayVillage) {
                    locInfo += " (" + location + ")";
                }
                y += DrawWrappedText(locInfo, fontTamilMedium, Brushes.Black, leftMargin, y, width, leftAlign) + 3;
            }

            // Names (Initial. Name1 - Initial2. Name2)
            string giverNameLine = "";
            if (!string.IsNullOrEmpty(initial)) giverNameLine += $"{initial}. ";
            giverNameLine += name1;
            if (!string.IsNullOrEmpty(initial2) || !string.IsNullOrEmpty(name2)) {
                giverNameLine += " - ";
                if (!string.IsNullOrEmpty(initial2)) giverNameLine += $"{initial2}. ";
                giverNameLine += name2;
            }
            
            y += DrawWrappedText(giverNameLine, fontBanner, Brushes.Black, 0, y, width, centerAlign) + 5;

            // Extra Info (Occupation, Phone, Description, Customer No)
            string combinedOcc = occupation;
            if (!string.IsNullOrEmpty(occupation2)) { 
                if (!string.IsNullOrEmpty(combinedOcc)) combinedOcc += " / ";
                combinedOcc += occupation2; 
            }
            if (!string.IsNullOrEmpty(combinedOcc)) {
                y += DrawWrappedText(combinedOcc, fontTamilRegular, Brushes.Black, leftMargin, y, width, leftAlign) + 3;
            }

            if (!string.IsNullOrEmpty(phone)) {
                y += DrawWrappedText("கைபேசி: " + phone, fontTamilRegular, Brushes.Black, leftMargin, y, width, leftAlign) + 2;
            }
            if (!string.IsNullOrEmpty(description)) {
                y += DrawWrappedText("விவரம்: " + description, fontTamilRegular, Brushes.Black, leftMargin, y, width, leftAlign) + 2;
            }
            if (!string.IsNullOrEmpty(customerNo)) {
                y += DrawWrappedText("சிட்டை எண்: " + customerNo, fontTamilRegular, Brushes.Black, leftMargin, y, width, leftAlign) + 2;
            }

            // 11. SEPARATOR (Dashed)
            DrawDashedLine(g, y, width);
            y += 5;

            // 12. AMOUNT
            g.DrawString("தொகை", fontAmountLabel, Brushes.Black, leftMargin, y + 2);
            string amtText = $"₹{formattedAmount}";
            g.DrawString(amtText, fontAmountValue, Brushes.Black, rightMargin, y, rightAlign);
            y += 35;

            // 13. FOOTER
            DrawSolidLine(g, y, width);
            y += 5;
            DrawDashedLine(g, y, width);
            y += 8;
            g.DrawString("நன்றி !", fontTamilLarge, Brushes.Black, width/2, y, centerAlign);
            y += 20;
            g.DrawString("VVS A to Z Function Contractor", fontEngMedium, Brushes.Black, width/2, y, centerAlign);
            y += 20;
        }
        else
        {
            // === COUNTER COPY (COMPACT DESIGN) ===
            // No Logo, No Phones, Small Fonts

            g.DrawString("--- COUNTER COPY ---", fontCompactBold, Brushes.Black, width/2, y, centerAlign);
            y += 15;

            // Header Info
            string line1 = $"Bill: {billNo} | Comp: {compNo}";
            g.DrawString(line1, fontCompactRegular, Brushes.Black, 0, y, leftAlign);
            g.DrawString(dateStr, fontCompactRegular, Brushes.Black, width, y, rightAlign);
            y += 12;

            DrawSolidLine(g, y, width);
            y += 4;

            // Function Name
            y += DrawWrappedText(functionName, fontCompactMedium, Brushes.Black, 0, y, width, centerAlign) + 4;

            DrawDashedLine(g, y, width);
            y += 4;

            // Village
            string displayVillageCC = !string.IsNullOrEmpty(village) ? village : location;
            if (!string.IsNullOrEmpty(displayVillageCC)) {
                y += DrawWrappedText(displayVillageCC, fontCompactRegular, Brushes.Black, 0, y, width, leftAlign) + 2;
            }

            // Name
            string fullNameStrCC = "";
            if (!string.IsNullOrEmpty(initial)) fullNameStrCC += $"{initial}. ";
            fullNameStrCC += name1;
            if (!string.IsNullOrEmpty(initial2) || !string.IsNullOrEmpty(name2)) {
                fullNameStrCC += " - ";
                if (!string.IsNullOrEmpty(initial2)) fullNameStrCC += $"{initial2}. ";
                fullNameStrCC += name2;
            }
            y += DrawWrappedText(fullNameStrCC, fontCompactLarge, Brushes.Black, 0, y, width, leftAlign) + 4;

            // Compact details for counter copy
            string extraCC = "";
            if (!string.IsNullOrEmpty(occupation)) extraCC += occupation;
            if (!string.IsNullOrEmpty(phone)) { if(extraCC!="") extraCC += " / "; extraCC += phone; }
            if (!string.IsNullOrEmpty(customerNo)) { if(extraCC!="") extraCC += " / "; extraCC += "No:" + customerNo; }
            
            if (!string.IsNullOrEmpty(extraCC)) {
                y += DrawWrappedText(extraCC, fontCompactRegular, Brushes.Black, 0, y, width, leftAlign) + 2;
            }

            DrawDashedLine(g, y, width);
            y += 4;

            // Amount
            g.DrawString("தொகை", fontCompactMedium, Brushes.Black, 0, y);
            g.DrawString($"₹{formattedAmount}", fontCompactLarge, Brushes.Black, width, y, rightAlign);
            y += 18;

            DrawSolidLine(g, y, width);
            y += 4;

            g.DrawString("நன்றி !", fontCompactMedium, Brushes.Black, width/2, y, centerAlign);
            y += 15;
            
            g.DrawString("VVS மொய் டெக்", fontCompactMedium, Brushes.Black, width/2, y, centerAlign);
            y += 15;
        }

        e.HasMorePages = false;
    }

    private void DrawSolidLine(Graphics g, float y, float w)
    {
        using (Pen p = new Pen(Color.Black, 1))
        {
            g.DrawLine(p, 0, y, w, y);
        }
    }

    private void DrawDashedLine(Graphics g, float y, float w)
    {
        using (Pen p = new Pen(Color.Black, 1))
        {
            p.DashStyle = System.Drawing.Drawing2D.DashStyle.Dash;
            g.DrawLine(p, 0, y, w, y);
        }
    }

    private void DrawLine(Graphics g, float y, float w)
    {
        using (Pen p = new Pen(Color.Black, 1))
        {
            g.DrawLine(p, 0, y, w, y);
        }
    }

    // Denomination Summary Print State
    private JsonElement currentDenomSummaryData;

    private void HandlePrintDenomSummaryRequest(Dictionary<string, JsonElement> message)
    {
        try
        {
            Debug.WriteLine("=== HandlePrintDenomSummaryRequest START ===");
            
            if (string.IsNullOrEmpty(selectedPrinter))
            {
                MessageBox.Show("No printer configured! Please configure printer.", "Print Error", MessageBoxButtons.OK, MessageBoxIcon.Error);
                PromptForPrinterSelection(selectedPrinter);
                if (string.IsNullOrEmpty(selectedPrinter))
                {
                    Debug.WriteLine("User cancelled printer selection");
                    return;
                }
            }

            Debug.WriteLine($"Selected printer: {selectedPrinter}");

            // Check if message has 'data' key
            if (!message.ContainsKey("data"))
            {
                MessageBox.Show("Print request missing 'data' field!", "Print Error", MessageBoxButtons.OK, MessageBoxIcon.Error);
                Debug.WriteLine("ERROR: message does not contain 'data' key");
                return;
            }

            currentDenomSummaryData = message["data"];
            Debug.WriteLine($"Retrieved denomination data. Type: {currentDenomSummaryData.ValueKind}");

            // Log the data structure
            if (currentDenomSummaryData.TryGetProperty("functionName", out var fnName))
            {
                Debug.WriteLine($"Function Name: {fnName}");
            }
            if (currentDenomSummaryData.TryGetProperty("editedDenoms", out var edited))
            {
                Debug.WriteLine("Has editedDenoms data");
            }
            if (currentDenomSummaryData.TryGetProperty("allSummary", out var all))
            {
                Debug.WriteLine("Has allSummary data");
            }

            Debug.WriteLine("Creating PrintDocument...");
            using (PrintDocument pd = new PrintDocument())
            {
                pd.PrinterSettings.PrinterName = selectedPrinter;
                Debug.WriteLine($"PrintDocument created for: {pd.PrinterSettings.PrinterName}");
                
                pd.PrintPage += new PrintPageEventHandler(PrintDenomSummaryPage);
                Debug.WriteLine("PrintPage event handler attached");
                
                Debug.WriteLine("Calling pd.Print()...");
                pd.Print();
                Debug.WriteLine("pd.Print() completed successfully");
            }

            MessageBox.Show($"Denomination summary sent to printer: {selectedPrinter}", "Print Success", MessageBoxButtons.OK, MessageBoxIcon.Information);
            Debug.WriteLine("=== HandlePrintDenomSummaryRequest END (SUCCESS) ===");
        }
        catch (Exception ex)
        {
            Debug.WriteLine($"=== HandlePrintDenomSummaryRequest ERROR ===");
            Debug.WriteLine($"Error: {ex.Message}");
            Debug.WriteLine($"Stack Trace: {ex.StackTrace}");
            MessageBox.Show($"Error printing denomination summary:\n\n{ex.Message}\n\nStack Trace:\n{ex.StackTrace}", "Print Error", MessageBoxButtons.OK, MessageBoxIcon.Error);
        }
    }

    private void PrintDenomSummaryPage(object sender, PrintPageEventArgs e)
    {
        Graphics g = e.Graphics;
        float width = e.PageSettings.PrintableArea.Width;
        float y = 0;

        // Fonts - matching payment receipt design
        Font titleFont = new Font("Nirmala UI", 16, FontStyle.Bold); 
        Font subTitleFont = new Font("Nirmala UI", 10, FontStyle.Regular);
        Font boldFont = new Font("Nirmala UI", 11, FontStyle.Bold);
        Font regularFont = new Font("Nirmala UI", 10, FontStyle.Regular);
        Font smallFont = new Font("Nirmala UI", 8, FontStyle.Regular);
        Font placeFont = new Font("Nirmala UI", 10, FontStyle.Regular);
        Font tableHeaderFont = new Font("Arial", 9, FontStyle.Bold);
        Font tableDataFont = new Font("Arial", 8, FontStyle.Regular);
        Font totalFont = new Font("Arial", 10, FontStyle.Bold);
        Font timestampFont = new Font("Arial", 7, FontStyle.Regular);

        StringFormat centerAlign = new StringFormat { Alignment = StringAlignment.Center };
        StringFormat leftAlign = new StringFormat { Alignment = StringAlignment.Near };
        StringFormat rightAlign = new StringFormat { Alignment = StringAlignment.Far };

        // Helper to get string from JsonElement
        string GetStr(JsonElement elem, string key)
        {
            if (!elem.TryGetProperty(key, out var val)) return "";
            if (val.ValueKind == JsonValueKind.Number) return val.ToString();
            if (val.ValueKind == JsonValueKind.String) return val.GetString() ?? "";
            return val.ToString();
        }

        int GetInt(JsonElement elem, string key)
        {
            if (!elem.TryGetProperty(key, out var val)) return 0;
            if (val.ValueKind == JsonValueKind.Number) return val.GetInt32();
            if (int.TryParse(val.ToString(), out int result)) return result;
            return 0;
        }

        decimal GetDecimal(JsonElement elem, string key)
        {
            if (!elem.TryGetProperty(key, out var val)) return 0;
            if (val.ValueKind == JsonValueKind.Number) return val.GetDecimal();
            if (decimal.TryParse(val.ToString(), out decimal result)) return result;
            return 0;
        }

        // Format number with Indian numbering
        string FormatAmount(decimal amount)
        {
            try
            {
                System.Globalization.CultureInfo indian = new System.Globalization.CultureInfo("en-IN");
                return amount.ToString("N0", indian);
            }
            catch
            {
                return amount.ToString("N0");
            }
        }

        // ====================================
        // HEADER SECTION - MATCHING PAYMENT RECEIPT DESIGN
        // ====================================
        
        // 1. Header Logo (Try to load logo first)
        string logoPath = null;
        string[] possiblePaths = new string[]
        {
            Path.Combine(Application.StartupPath, "Properties", "vvs_logo.jpg"),
            Path.Combine(Application.StartupPath, "vvs_logo.jpg"),
            Path.Combine(AppDomain.CurrentDomain.BaseDirectory, "Properties", "vvs_logo.jpg"),
            Path.Combine(AppDomain.CurrentDomain.BaseDirectory, "vvs_logo.jpg")
        };

        foreach (var path in possiblePaths)
        {
            if (File.Exists(path))
            {
                logoPath = path;
                Debug.WriteLine($"Logo found at: {logoPath}");
                break;
            }
        }

        if (!string.IsNullOrEmpty(logoPath))
        {
            try 
            {
                using (Image img = Image.FromFile(logoPath))
                {
                    float imgW = Math.Min(width, 250); 
                    float ratio = (float)img.Height / img.Width;
                    float imgH = imgW * ratio;
                    float imgX = (width - imgW) / 2;
                    g.DrawImage(img, imgX, y, imgW, imgH);
                    y += imgH + 5;
                }
            }
            catch (Exception ex)
            {
                Debug.WriteLine($"Error loading logo: {ex.Message}");
                // Fall back to text
                g.DrawString("VVS Moi Net", titleFont, Brushes.Black, width / 2, y, centerAlign);
                y += 25;
            }
        }
        else
        {
            Debug.WriteLine("Logo file not found in any path");
            g.DrawString("VVS Moi Net", titleFont, Brushes.Black, width / 2, y, centerAlign);
            y += 25;
        }

        // 2. Place Name (கடமலை)
        g.DrawString("கடமலை", placeFont, Brushes.Black, width / 2, y, centerAlign);
        y += 18;
        
        // 3. Phone Numbers (matching receipt format)
        g.DrawString("915 925 925 0", boldFont, Brushes.Black, width / 2, y, centerAlign);
        y += 20;
        g.DrawString("915 925 925 1", boldFont, Brushes.Black, width / 2, y, centerAlign);
        y += 20;
        g.DrawString("915 925 925 2", boldFont, Brushes.Black, width / 2, y, centerAlign);
        y += 20;

        // Double line separator (like payment receipt)
        DrawLine(g, y, width);
        DrawLine(g, y, width);
        y += 5;

        // 4. Denomination Summary Title
        g.DrawString("DENOMINATION SUMMARY", boldFont, Brushes.Black, width / 2, y, centerAlign);
        y += 18;

        // Function Name (Event Name)
        string functionName = GetStr(currentDenomSummaryData, "functionName");
        if (!string.IsNullOrEmpty(functionName))
        {
            // Word wrap if too long
            if (functionName.Length > 30)
            {
                var words = functionName.Split(' ');
                string line1 = "";
                string line2 = "";
                bool onFirstLine = true;
                foreach (var word in words)
                {
                    if (onFirstLine && (line1 + " " + word).Length < 30)
                    {
                        line1 += (line1.Length > 0 ? " " : "") + word;
                    }
                    else
                    {
                        onFirstLine = false;
                        line2 += (line2.Length > 0 ? " " : "") + word;
                    }
                }
                g.DrawString(line1, regularFont, Brushes.Black, width / 2, y, centerAlign);
                y += 15;
                if (!string.IsNullOrEmpty(line2))
                {
                    g.DrawString(line2, regularFont, Brushes.Black, width / 2, y, centerAlign);
                    y += 15;
                }
            }
            else
            {
                g.DrawString(functionName, regularFont, Brushes.Black, width / 2, y, centerAlign);
                y += 15;
            }
        }

        // Computer info and timestamp in single line (like payment receipt)
        string currentComputer = GetStr(currentDenomSummaryData, "currentComputer");
        string timestamp = DateTime.Now.ToString("dd-MM-yyyy h:mm tt");
        string infoLine = "";
        if (!string.IsNullOrEmpty(currentComputer)) infoLine += $"Comp: {currentComputer} | ";
        infoLine += timestamp;
        
        g.DrawString(infoLine, smallFont, Brushes.Black, width / 2, y, centerAlign);
        y += 15;

        // Top border line
        DrawLine(g, y, width);
        y += 5;

        // ====================================
        // TABLE HEADER
        // ====================================
        float colWidth = width / 3f;
        float col1X = 0;
        float col2X = colWidth;
        float col3X = colWidth * 2;

        // Draw table header background (simulated with border)
        DrawLine(g, y, width);
        y += 2;

        g.DrawString("Denomination", tableHeaderFont, Brushes.Black, col1X + 5, y, leftAlign);
        g.DrawString("Count", tableHeaderFont, Brushes.Black, col2X + 5, y, leftAlign);
        g.DrawString("Amount", tableHeaderFont, Brushes.Black, col3X + 5, y, leftAlign);
        y += 14;

        DrawLine(g, y, width);
        y += 2;

        // ====================================
        // TABLE DATA - Get denomination data
        // ====================================
        
        // Try to get edited denoms first, fall back to allSummary
        JsonElement denomData = currentDenomSummaryData;
        if (currentDenomSummaryData.TryGetProperty("editedDenoms", out var editedDenoms))
        {
            denomData = editedDenoms;
        }
        else if (currentDenomSummaryData.TryGetProperty("allSummary", out var allSummary))
        {
            denomData = allSummary;
        }

        // Get denomination counts
        int d500 = GetInt(denomData, "denom_500");
        int d200 = GetInt(denomData, "denom_200");
        int d100 = GetInt(denomData, "denom_100");
        int d50 = GetInt(denomData, "denom_50");
        int d20 = GetInt(denomData, "denom_20");
        int d10 = GetInt(denomData, "denom_10");
        int d5 = GetInt(denomData, "denom_5");
        int d2 = GetInt(denomData, "denom_2");
        int d1 = GetInt(denomData, "denom_1");

        // Calculate individual amounts
        decimal amt500 = d500 * 500;
        decimal amt200 = d200 * 200;
        decimal amt100 = d100 * 100;
        decimal amt50 = d50 * 50;
        decimal amt20 = d20 * 20;
        decimal amt10 = d10 * 10;
        decimal amt5 = d5 * 5;
        decimal amt2 = d2 * 2;
        decimal amt1 = d1 * 1;

        // Calculate grand total
        decimal grandTotal = amt500 + amt200 + amt100 + amt50 + amt20 + amt10 + amt5 + amt2 + amt1;

        // Draw denomination rows (₹500)
        g.DrawString("₹500", tableDataFont, Brushes.Black, col1X + 5, y, leftAlign);
        g.DrawString(d500.ToString(), tableDataFont, Brushes.Black, col2X + 5, y, leftAlign);
        g.DrawString($"₹{FormatAmount(amt500)}", tableDataFont, Brushes.Black, col3X + 5, y, leftAlign);
        y += 12;
        DrawLine(g, y, width);
        y += 2;

        // ₹200
        g.DrawString("₹200", tableDataFont, Brushes.Black, col1X + 5, y, leftAlign);
        g.DrawString(d200.ToString(), tableDataFont, Brushes.Black, col2X + 5, y, leftAlign);
        g.DrawString($"₹{FormatAmount(amt200)}", tableDataFont, Brushes.Black, col3X + 5, y, leftAlign);
        y += 12;
        DrawLine(g, y, width);
        y += 2;

        // ₹100
        g.DrawString("₹100", tableDataFont, Brushes.Black, col1X + 5, y, leftAlign);
        g.DrawString(d100.ToString(), tableDataFont, Brushes.Black, col2X + 5, y, leftAlign);
        g.DrawString($"₹{FormatAmount(amt100)}", tableDataFont, Brushes.Black, col3X + 5, y, leftAlign);
        y += 12;
        DrawLine(g, y, width);
        y += 2;

        // ₹50
        g.DrawString("₹50", tableDataFont, Brushes.Black, col1X + 5, y, leftAlign);
        g.DrawString(d50.ToString(), tableDataFont, Brushes.Black, col2X + 5, y, leftAlign);
        g.DrawString($"₹{FormatAmount(amt50)}", tableDataFont, Brushes.Black, col3X + 5, y, leftAlign);
        y += 12;
        DrawLine(g, y, width);
        y += 2;

        // ₹20
        g.DrawString("₹20", tableDataFont, Brushes.Black, col1X + 5, y, leftAlign);
        g.DrawString(d20.ToString(), tableDataFont, Brushes.Black, col2X + 5, y, leftAlign);
        g.DrawString($"₹{FormatAmount(amt20)}", tableDataFont, Brushes.Black, col3X + 5, y, leftAlign);
        y += 12;
        DrawLine(g, y, width);
        y += 2;

        // ₹10
        g.DrawString("₹10", tableDataFont, Brushes.Black, col1X + 5, y, leftAlign);
        g.DrawString(d10.ToString(), tableDataFont, Brushes.Black, col2X + 5, y, leftAlign);
        g.DrawString($"₹{FormatAmount(amt10)}", tableDataFont, Brushes.Black, col3X + 5, y, leftAlign);
        y += 12;
        DrawLine(g, y, width);
        y += 2;

        // ₹5
        g.DrawString("₹5", tableDataFont, Brushes.Black, col1X + 5, y, leftAlign);
        g.DrawString(d5.ToString(), tableDataFont, Brushes.Black, col2X + 5, y, leftAlign);
        g.DrawString($"₹{FormatAmount(amt5)}", tableDataFont, Brushes.Black, col3X + 5, y, leftAlign);
        y += 12;
        DrawLine(g, y, width);
        y += 2;

        // ₹2
        g.DrawString("₹2", tableDataFont, Brushes.Black, col1X + 5, y, leftAlign);
        g.DrawString(d2.ToString(), tableDataFont, Brushes.Black, col2X + 5, y, leftAlign);
        g.DrawString($"₹{FormatAmount(amt2)}", tableDataFont, Brushes.Black, col3X + 5, y, leftAlign);
        y += 12;
        DrawLine(g, y, width);
        y += 2;

        // ₹1
        g.DrawString("₹1", tableDataFont, Brushes.Black, col1X + 5, y, leftAlign);
        g.DrawString(d1.ToString(), tableDataFont, Brushes.Black, col2X + 5, y, leftAlign);
        g.DrawString($"₹{FormatAmount(amt1)}", tableDataFont, Brushes.Black, col3X + 5, y, leftAlign);
        y += 12;
        DrawLine(g, y, width);
        y += 2;

        // ====================================
        // GRAND TOTAL ROW
        // ====================================
        y += 3;
        g.DrawString("GRAND TOTAL", totalFont, Brushes.Black, col1X + 5, y, leftAlign);
        g.DrawString($"₹{FormatAmount(grandTotal)}", totalFont, Brushes.Black, col3X + 5, y, leftAlign);
        y += 15;

        DrawLine(g, y, width);
        y += 10;

        // ====================================
        // FOOTER SECTION - MATCHING PAYMENT RECEIPT
        // ====================================
        
        // Transaction count if available
        if (currentDenomSummaryData.TryGetProperty("allSummary", out var summary))
        {
            int transCount = GetInt(summary, "transaction_count");
            if (transCount > 0)
            {
                string transLine = $"Transactions: {transCount}";
                g.DrawString(transLine, regularFont, Brushes.Black, width / 2, y, centerAlign);
                y += 18;
            }
        }

        // Line before thank you section
        DrawLine(g, y, width);
        y += 10;

        // Thank you message (like payment receipt)
        g.DrawString("தங்கள் வருகைக்கு நன்றி !!!", boldFont, Brushes.Black, width / 2, y, centerAlign);
        y += 20;

        // Cut spacing
        y += 20;

        e.HasMorePages = false;
    }

    private void HandlePrintBarcodeRequest(Dictionary<string, JsonElement> data)
    {
         try
        {
            if (!data.ContainsKey("data")) return;

            var itemData = data["data"];
            var stickerData = new StickerData
            {
                ItemName = itemData.TryGetProperty("itemName", out var itemName) ? itemName.GetString() ?? "" : "",
                Barcode = itemData.TryGetProperty("barcode", out var barcode) ? barcode.GetString() ?? "" : "",
                Mrp = itemData.TryGetProperty("mrp", out var mrp) ? mrp.GetString() ?? "0.00" : "0.00",
                TrendPrice = itemData.TryGetProperty("salesPrice", out var salesPrice) ? salesPrice.GetString() ?? "0.00" : "0.00",
                Size = itemData.TryGetProperty("size", out var size) ? size.GetString() ?? "" : "",
                CompanyName = itemData.TryGetProperty("companyName", out var companyName) ? companyName.GetString() ?? "Trend Makers" : "Trend Makers"
            };

            int quantity = 1;
            if (data.ContainsKey("quantity") && data["quantity"].ValueKind == JsonValueKind.Number)
            {
                quantity = data["quantity"].GetInt32();
            }

            StickerLayout layout = quantity == 1 ? StickerLayout.SingleLabel : StickerLayout.Auto;
            var printer = new StickerPrinter();
            printer.Print(stickerData, quantity, layout);
        }
        catch (Exception ex)
        {
            MessageBox.Show($"Error printing barcode: {ex.Message}");
        }
    }

    private void HandlePrintCartStickerRequest(Dictionary<string, JsonElement> data)
    {
         try
        {
            if (!data.ContainsKey("items")) return;
            var itemsElement = data["items"];
            var printer = new StickerPrinter();
            
            foreach (var itemElement in itemsElement.EnumerateArray())
            {
                var stickerData = new StickerData
                {
                    ItemName = itemElement.TryGetProperty("itemName", out var itemName) ? itemName.GetString() ?? "" : "",
                    Barcode = itemElement.TryGetProperty("barcode", out var barcode) ? barcode.GetString() ?? "" : "",
                    Mrp = itemElement.TryGetProperty("mrp", out var mrp) ? mrp.GetString() ?? "0.00" : "0.00",
                    TrendPrice = itemElement.TryGetProperty("salesPrice", out var salesPrice) ? salesPrice.GetString() ?? "0.00" : "0.00",
                };
                
                int quantity = 1;
                if (itemElement.TryGetProperty("quantity", out var qtyElement)) quantity = qtyElement.GetInt32();
                
                 printer.Print(stickerData, quantity, quantity == 1 ? StickerLayout.SingleLabel : StickerLayout.Auto, false);
            }
            MessageBox.Show("Batch print processed.");
        }
        catch (Exception ex)
        {
            MessageBox.Show($"Error batch print: {ex.Message}");
        }
    }

    protected override void OnFormClosing(FormClosingEventArgs e)
    {
        base.OnFormClosing(e);

        if (networkMonitor != null)
        {
            networkMonitor.Tick -= NetworkMonitor_Tick;
            networkMonitor.Stop();
            networkMonitor.Dispose();
        }

        NetworkChange.NetworkAvailabilityChanged -= NetworkAvailabilityChanged;

        if (webView != null)
        {
            webView.NavigationStarting -= WebView_NavigationStarting;
            webView.NavigationCompleted -= WebView_NavigationCompleted;
            webView.Dispose();
        }
    }
}
