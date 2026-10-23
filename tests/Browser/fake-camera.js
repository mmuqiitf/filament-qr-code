/**
 * Init script: replaces the camera with a canvas stream that paints a QR code.
 * `capabilities` is reported by the track so torch / zoom UI can be exercised.
 */
export function installFakeCamera({ svg, capabilities = {} }) {
    const canvas = document.createElement('canvas');
    canvas.width = 640;
    canvas.height = 480;
    const context = canvas.getContext('2d');
    const image = new Image();
    image.src = 'data:image/svg+xml;base64,' + btoa(svg);

    const paint = () => {
        context.fillStyle = '#fff';
        context.fillRect(0, 0, canvas.width, canvas.height);
        if (image.complete) context.drawImage(image, 230, 150, 180, 180);
        requestAnimationFrame(paint);
    };
    paint();

    window.__fakeCameraConstraints = [];

    const fakeStream = () => {
        const stream = canvas.captureStream(15);
        for (const track of stream.getVideoTracks()) {
            track.getCapabilities = () => ({ ...capabilities });
            track.getSettings = () => ({ width: 640, height: 480, deviceId: 'fake-camera' });
            track.applyConstraints = async (constraints) => {
                window.__fakeCameraConstraints.push(constraints);
            };
        }

        return stream;
    };

    const media = navigator.mediaDevices ?? {};
    media.getUserMedia = async () => fakeStream();
    media.enumerateDevices = async () => [
        { kind: 'videoinput', deviceId: 'fake-camera', label: 'Fake back camera', groupId: 'fake' },
    ];
}
