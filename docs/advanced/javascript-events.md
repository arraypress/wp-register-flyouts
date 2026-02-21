# JavaScript Events

Listen for flyout events in JavaScript:

```javascript
// Flyout opened
document.addEventListener('flyout:opened', function (e) {
    console.log('Flyout opened:', e.detail.id);
});

// Flyout closed
document.addEventListener('flyout:closed', function (e) {
    console.log('Flyout closed:', e.detail.id);
});

// Data loaded
document.addEventListener('flyout:loaded', function (e) {
    console.log('Data loaded:', e.detail.data);
});

// Before save
document.addEventListener('flyout:before_save', function (e) {
    e.detail.data.extra = 'value';
});

// After save
document.addEventListener('flyout:saved', function (e) {
    console.log('Saved:', e.detail.result);
});

// Save error
document.addEventListener('flyout:save_error', function (e) {
    console.error('Save failed:', e.detail.error);
});
```
