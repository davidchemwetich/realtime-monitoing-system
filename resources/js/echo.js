// import Echo from 'laravel-echo';
// import Pusher from 'pusher-js';

// window.Pusher = Pusher;

// window.Echo = new Echo({
//     broadcaster: 'reverb',
//     key: import.meta.env.VITE_REVERB_APP_KEY,
//     wsHost: import.meta.env.VITE_REVERB_HOST,
//     wsPort: import.meta.env.VITE_REVERB_PORT ?? 8080,
//     wsPort: import.meta.env.VITE_REVERB_PORT ?? 8080,
//     scheme: import.meta.env.VITE_REVERB_SCHEME ?? 'http',
//     enabledTransports: ['ws', 'ws'],
// });

import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

window.Pusher = Pusher;

/**
 * We'll add some console logs here to debug the environment variables
 * that Vite is passing to our JavaScript.
 */
console.log('%c--- Echo Configuration (echo.js) ---', 'color: #9333ea; font-weight: bold;');
console.log('VITE_REVERB_APP_KEY:', import.meta.env.VITE_REVERB_APP_KEY);
console.log('VITE_REVERB_HOST:', import.meta.env.VITE_REVERB_HOST);
console.log('VITE_REVERB_PORT:', import.meta.env.VITE_REVERB_PORT);
console.log('VITE_REVERB_SCHEME:', import.meta.env.VITE_REVERB_SCHEME);

const reverbHost = import.meta.env.VITE_REVERB_HOST;
const reverbPort = import.meta.env.VITE_REVERB_PORT;
const reverbScheme = import.meta.env.VITE_REVERB_SCHEME;

// Determine if TLS should be forced based on the scheme.
const forceTLS = (reverbScheme ?? 'http') === 'https';

console.log('Calculated forceTLS:', forceTLS);
console.log('------------------------------------');

window.Echo = new Echo({
    broadcaster: 'reverb',
    key: import.meta.env.VITE_REVERB_APP_KEY,

    /**
     * The wsHost and wsPort should point to your Reverb server.
     * By default, this is localhost and 8080 for local development.
     */
    wsHost: reverbHost,
    wsPort: reverbPort,
    wssPort: reverbPort, // It's good practice to set wssPort to the same port

    /**
     * This is the most important setting. We explicitly set forceTLS to false
     * when our VITE_REVERB_SCHEME is 'http'. This prevents the wss:// connection.
     */
    forceTLS: forceTLS,

    /**
     * We enable both transports, but forceTLS will determine which one is used.
     */
    enabledTransports: ['ws', 'wss'],
});
