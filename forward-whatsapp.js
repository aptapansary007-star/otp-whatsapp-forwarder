const express = require('express');
const { Client, LocalAuth } = require('whatsapp-web.js');
const qrcode = require('qrcode-terminal');
require('dotenv').config();

const app = express();
app.use(express.json());

// Initialize WhatsApp client
const client = new Client({
    authStrategy: new LocalAuth({
        clientId: "otp-client"
    }),
    puppeteer: {
        headless: true,
        args: [
            '--no-sandbox',
            '--disable-setuid-sandbox',
            '--disable-dev-shm-usage',
            '--disable-accelerated-2d-canvas',
            '--no-first-run',
            '--no-zygote',
            '--single-process',
            '--disable-gpu'
        ]
    }
});

let isClientReady = false;

// WhatsApp client events
client.on('qr', (qr) => {
    console.log('QR Code received, scan with WhatsApp:');
    qrcode.generate(qr, {small: true});
});

client.on('ready', () => {
    console.log('WhatsApp client is ready!');
    isClientReady = true;
});

client.on('authenticated', () => {
    console.log('WhatsApp authenticated successfully');
});

client.on('auth_failure', msg => {
    console.error('Authentication failed:', msg);
});

client.on('disconnected', (reason) => {
    console.log('WhatsApp client disconnected:', reason);
    isClientReady = false;
});

// Initialize WhatsApp client
client.initialize();

// API endpoint to send WhatsApp message
app.post('/send-whatsapp', async (req, res) => {
    try {
        if (!isClientReady) {
            return res.status(503).json({
                status: 'error',
                message: 'WhatsApp client not ready'
            });
        }

        const { phone, otp } = req.body;

        if (!phone || !otp) {
            return res.status(400).json({
                status: 'error',
                message: 'Phone and OTP are required'
            });
        }

        // Your personal WhatsApp number (from environment variable)
        const targetNumber = process.env.PERSONAL_WHATSAPP_NUMBER;
        
        if (!targetNumber) {
            return res.status(500).json({
                status: 'error',
                message: 'WhatsApp target number not configured'
            });
        }

        // Format message
        const message = `🔐 *New OTP Request*\n\n📱 *User:* ${phone}\n🔑 *OTP:* ${otp}\n⏰ *Time:* ${new Date().toLocaleString('en-IN', {timeZone: 'Asia/Kolkata'})}`;

        // Send message to your personal WhatsApp
        const chatId = targetNumber.includes('@') ? targetNumber : `${targetNumber}@c.us`;
        
        await client.sendMessage(chatId, message);

        res.json({
            status: 'success',
            message: 'OTP forwarded to WhatsApp successfully'
        });

    } catch (error) {
        console.error('Error sending WhatsApp message:', error);
        res.status(500).json({
            status: 'error',
            message: 'Failed to send WhatsApp message'
        });
    }
});

// Health check endpoint
app.get('/health', (req, res) => {
    res.json({
        status: 'ok',
        whatsapp_ready: isClientReady,
        timestamp: new Date().toISOString()
    });
});

// Start server
const PORT = process.env.PORT || 3000;
app.listen(PORT, () => {
    console.log(`WhatsApp forwarder service running on port ${PORT}`);
});

// Graceful shutdown
process.on('SIGINT', async () => {
    console.log('Shutting down...');
    await client.destroy();
    process.exit(0);
});

process.on('SIGTERM', async () => {
    console.log('Shutting down...');
    await client.destroy();
    process.exit(0);
});
