document.addEventListener('DOMContentLoaded', function() {
    'use strict';

    var icon_down = '<svg class="CK__Icon--medium" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg" width="16" height="16"><path fill-rule="evenodd" clip-rule="evenodd" d="M1.146 4.646a.5.5 0 0 0 0 .708l6.5 6.5a.5.5 0 0 0 .708 0l6.5-6.5a.5.5 0 0 0-.708-.708L8 10.793 1.854 4.646a.5.5 0 0 0-.708 0Z" fill="#00639E"></path></svg>';

    function truncateContent() {
        document.querySelectorAll('.rcreviews--content').forEach(function(el) {
            var lineHeight = parseInt(window.getComputedStyle(el).getPropertyValue('line-height'), 10);
            var lines = Math.ceil(el.scrollHeight / lineHeight);

            if (2 < lines && !el.classList.contains('rcreviews--has-truncate')) {
                el.classList.add('rcreviews--has-truncate');
                el.classList.add('rcreviews--truncate');
                el.insertAdjacentHTML('afterend', '<div class="rcreviews--read-more-wrapper"><span class="rcreviews--read-more">Read More ' + icon_down + '</span></div>');
            }
        });
    }

    truncateContent();

    const btn = document.querySelector('.rcreviews--btn');
    if (btn) {
      btn.addEventListener('click', function() {
        document.querySelectorAll('.rcreviews--hidden-review').forEach(function(el) {
          el.classList.toggle('d-none');
        });
    
        if (typeof truncateContent === 'function') {
          truncateContent();
        }
    
        const label = document.querySelector('.rcreviews--label');
        const count = document.querySelector('.rcreviews--count');
        if (label) {
          label.classList.toggle('active');
        }
        if (count) {
          count.classList.toggle('d-none');
        }
    
        if (label && label.classList.contains('active')) {
          label.textContent = 'Show less';
        } else if (label) {
          label.textContent = 'Show';
        }
      });
    }
    
    document.addEventListener('click', function(e) {
      if (e.target.matches('.rcreviews--read-more')) {
        const precedingElement = e.target.parentElement?.previousElementSibling;
        if (precedingElement) {
          precedingElement.classList.remove('rcreviews--truncate');
        }
        if (e.target.parentElement) {
          e.target.parentElement.remove();
        }
        console.log('click');
      }
    });
    
});