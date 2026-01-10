import { Html5Qrcode, Html5QrcodeSupportedFormats } from "html5-qrcode";

export default function qrScannerComponent({
  statePath = null,
  cameraFacing = "environment",
  scanMode = "single",
  scanDelay = 1500,
  fps = 30,
  qrboxSize = 250,
  showPreview = true,
  beepOnScan = true,
  vibrateOnScan = true,
}) {
  return {
    // Configuration
    statePath,
    cameraFacing,
    scanMode,
    scanDelay,
    fps,
    qrboxSize,
    showPreview,
    beepOnScan,
    vibrateOnScan,

    // State
    devices: [],
    selectedDeviceId: null,
    html5Qrcode: null,
    isLoading: true,
    isScanning: false,
    hasError: false,
    errorMessage: "",
    lastScannedValue: "",
    lastScanTime: 0,
    showManualInput: false,
    manualValue: "",
    readerId: `qr-reader-${Math.random().toString(36).substring(7)}`,

    init() {
      this.$nextTick(() => {
        this.loadCameras();
      });

      // Listen for reset events from Livewire
      if (this.$wire) {
        this.$wire.$on("scanner-reset-required", () => {
          this.lastScannedValue = "";
        });

        this.$wire.$on("camera-switch-required", (facing) => {
          this.cameraFacing = facing;
          this.switchCameraFacing();
        });
      }
    },

    async loadCameras() {
      this.isLoading = true;
      this.hasError = false;

      try {
        const devices = await Html5Qrcode.getCameras();
        this.devices = devices;

        if (devices.length > 0) {
          this.selectedDeviceId = this.selectBestCamera(devices);
          await this.startScanning();
        } else {
          this.hasError = true;
          this.errorMessage = "No camera found on this device.";
        }
      } catch (error) {
        this.hasError = true;
        this.errorMessage = "Camera permission denied. Please grant access.";
        console.error("[FilamentQrCode] Camera error:", error);
      } finally {
        this.isLoading = false;
      }
    },

    selectBestCamera(devices) {
      const facingMap = {
        environment: ["back", "rear", "environment"],
        user: ["front", "face", "user"],
        auto: ["back", "rear", "environment"],
      };

      const keywords = facingMap[this.cameraFacing] || facingMap["auto"];

      for (const keyword of keywords) {
        const camera = devices.find((d) =>
          d.label.toLowerCase().includes(keyword),
        );
        if (camera) return camera.id;
      }

      return devices[0]?.id;
    },

    async selectCamera(deviceId) {
      if (this.isScanning) {
        await this.stopScanning();
      }
      this.selectedDeviceId = deviceId;
      await this.startScanning();
    },

    async switchCameraFacing() {
      const targetKeywords =
        this.cameraFacing === "user"
          ? ["front", "face", "user"]
          : ["back", "rear", "environment"];

      for (const keyword of targetKeywords) {
        const camera = this.devices.find((d) =>
          d.label.toLowerCase().includes(keyword),
        );
        if (camera && camera.id !== this.selectedDeviceId) {
          await this.selectCamera(camera.id);
          return;
        }
      }
    },

    async startScanning() {
      if (!this.selectedDeviceId) return;

      this.hasError = false;
      this.errorMessage = "";

      const readerElement = document.getElementById(this.readerId);
      if (!readerElement) {
        console.error("[FilamentQrCode] Reader element not found");
        return;
      }

      if (!this.html5Qrcode) {
        this.html5Qrcode = new Html5Qrcode(this.readerId, {
          formatsToSupport: [
            Html5QrcodeSupportedFormats.QR_CODE,
            Html5QrcodeSupportedFormats.EAN_13,
            Html5QrcodeSupportedFormats.EAN_8,
            Html5QrcodeSupportedFormats.CODE_128,
            Html5QrcodeSupportedFormats.CODE_39,
            Html5QrcodeSupportedFormats.UPC_A,
            Html5QrcodeSupportedFormats.UPC_E,
          ],
          verbose: false,
        });
      }

      try {
        await this.html5Qrcode.start(
          this.selectedDeviceId,
          { fps: this.fps, qrbox: this.qrboxSize },
          (decodedText) => this.onScanSuccess(decodedText),
          () => {}, // Ignore scan failures
        );
        this.isScanning = true;
      } catch (error) {
        this.hasError = true;
        this.errorMessage = "Scanner error. Please try again.";
        console.error("[FilamentQrCode] Scanner error:", error);
      }
    },

    async stopScanning() {
      if (this.html5Qrcode && this.isScanning) {
        try {
          await this.html5Qrcode.stop();
        } catch (error) {
          console.error("[FilamentQrCode] Error stopping scanner:", error);
        }
        this.isScanning = false;
      }
    },

    onScanSuccess(decodedText) {
      const now = Date.now();
      if (now - this.lastScanTime < this.scanDelay) {
        return; // Debounce rapid scans
      }

      this.lastScanTime = now;
      this.lastScannedValue = decodedText;

      // Play beep sound
      if (this.beepOnScan) {
        this.playBeep();
      }

      // Vibrate device (mobile)
      if (this.vibrateOnScan && navigator.vibrate) {
        navigator.vibrate(100);
      }

      // Dispatch Alpine event
      this.$dispatch("qr-scanned", {
        value: decodedText,
        statePath: this.statePath,
      });

      // Dispatch to Livewire if available
      if (this.$wire) {
        this.$wire.dispatch("qr-code-scanned", {
          value: decodedText,
          field: this.statePath,
        });
      }

      // Window event for modal handling
      window.dispatchEvent(
        new CustomEvent("qr-scanned", {
          detail: { value: decodedText, statePath: this.statePath },
        }),
      );

      if (this.scanMode === "single") {
        this.stopScanning();
      }
    },

    playBeep() {
      try {
        const audioCtx = new (
          window.AudioContext || window.webkitAudioContext
        )();
        const oscillator = audioCtx.createOscillator();
        const gainNode = audioCtx.createGain();

        oscillator.connect(gainNode);
        gainNode.connect(audioCtx.destination);

        oscillator.frequency.value = 1800;
        oscillator.type = "sine";
        gainNode.gain.setValueAtTime(0.1, audioCtx.currentTime);
        gainNode.gain.exponentialRampToValueAtTime(
          0.01,
          audioCtx.currentTime + 0.1,
        );

        oscillator.start(audioCtx.currentTime);
        oscillator.stop(audioCtx.currentTime + 0.1);
      } catch (error) {
        console.warn("[FilamentQrCode] Could not play beep:", error);
      }
    },

    retryCamera() {
      this.loadCameras();
    },

    toggleManualInput() {
      this.showManualInput = !this.showManualInput;
      if (this.showManualInput) {
        this.$nextTick(() => {
          this.$refs.manualInput?.focus();
        });
      }
    },

    submitManualValue() {
      if (this.manualValue.trim()) {
        this.onScanSuccess(this.manualValue.trim());
        this.manualValue = "";
        this.showManualInput = false;
      }
    },

    destroy() {
      this.stopScanning();
      if (this.html5Qrcode) {
        try {
          this.html5Qrcode.clear();
        } catch (error) {
          console.warn("[FilamentQrCode] Error clearing scanner:", error);
        }
        this.html5Qrcode = null;
      }
    },
  };
}
