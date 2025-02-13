import * as mobiscroll from 'https://cdn.mobiscroll.com/5.22.2/js/mobiscroll.min.js';

mobiscroll.setOptions({
  theme: 'windows', // or any other theme you prefer
});

mobiscroll.select('#demo-multiple-select', {
  inputElement: document.getElementById('demo-multiple-select-input'),
});
