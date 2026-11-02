<?php
// El modo mock debe activarse explícitamente con USE_MOCK=1.
if (getenv('USE_MOCK') === false) {
    putenv('USE_MOCK=0');
}
if (getenv('API_URL') === false) {
    putenv('API_URL=http://localhost:8000');
}