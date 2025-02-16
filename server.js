const express = require('express');
const cors = require('cors');
const axios = require('axios');
const app = express();

// Middleware
app.use(cors());
app.use(express.json());
app.use(express.static('.'));

// Ecomail API configuration
const ECOMAIL_API_KEY = process.env.ECOMAIL_API_KEY || 'your-api-key';
const ECOMAIL_LIST_ID = process.env.ECOMAIL_LIST_ID || 'your-list-id';

// API endpoint pro zpracování formuláře
app.post('/api/send-email', async (req, res) => {
    try {
        const { name, email, phone, location, propertyType, message } = req.body;

        // Připravíme data pro Ecomail API
        const ecomailData = {
            subscriber_data: {
                email: email,
                name: name,
                phone: phone,
                custom_fields: {
                    location: location,
                    property_type: propertyType,
                    message: message
                }
            },
            trigger_autoresponders: true,
            update_existing: true,
            resubscribe: true
        };

        // Odeslání dat do Ecomail
        const response = await axios.post(
            `https://api.ecomail.cz/lists/${ECOMAIL_LIST_ID}/subscribe`,
            ecomailData,
            {
                headers: {
                    'Content-Type': 'application/json',
                    'key': ECOMAIL_API_KEY
                }
            }
        );

        console.log('Ecomail response:', response.data);
        res.json({ success: true, message: 'Formulář byl úspěšně odeslán' });
    } catch (error) {
        console.error('Chyba:', error.response?.data || error.message);
        res.status(500).json({ 
            success: false, 
            message: 'Došlo k chybě při zpracování formuláře',
            error: error.response?.data || error.message
        });
    }
});

const PORT = process.env.PORT || 3000;
app.listen(PORT, () => {
    console.log(`Server běží na portu ${PORT}`);
});
