/* Vendor panel: Add / Edit Media.
   Groups the shared admin Media Management form into numbered sections. It only
   MOVES the existing rows and columns (never re-creates them), so their ids,
   names, event handlers and the category show/hide logic keep working. */
(function () {
    // Add Media's form has id="mediaForm"; Edit Media's has none, so take the form
    // in the content card and give it the same id for the styles.
    var form = document.getElementById('mediaForm') || document.querySelector('.vp-body .card-body > form[enctype="multipart/form-data"]');
    if (!form) return;
    if (!form.id) form.id = 'mediaForm';

    var shell = form.closest('.card');
    if (shell) shell.classList.add('vmf-shell');

    var byId = function (id) { return document.getElementById(id); };
    var rowOf = function (el) { return el ? el.closest('.row') : null; };
    var colOf = function (el) { return el ? el.closest('[class*="col-"]') : null; };

    function section(step, title, hint) {
        var s = document.createElement('section');
        s.className = 'vmf-section';
        s.innerHTML = '<div class="vmf-head"><span class="vmf-step">' + step + '</span><div><h2></h2><p></p></div></div><div class="vmf-body"></div>';
        s.querySelector('h2').textContent = title;
        s.querySelector('p').textContent = hint;
        return s;
    }

    // Field rows: .row elements, plus id'd wrapper divs around a row (Edit Media's
    // #hoardingExtra is one), so a category block is never left outside the sections.
    var rows = Array.prototype.filter.call(form.children, function (el) {
        return el.classList.contains('row') || (el.tagName === 'DIV' && el.id && el.querySelector('.row'));
    });
    if (!rows.length) return;

    var titleRow = rowOf(form.querySelector('[name="media_title"]'));
    var sizeRow  = rowOf(byId('width'));
    var actions  = form.querySelector('.text-end');

    // Photos: the image / 360° columns move out of the size & price row.
    var photoCols = [colOf(byId('images')), colOf(form.querySelector('[name="panorama_image"]'))].filter(Boolean);

    var s1 = section(1, 'Media type & location', 'Choose the category first: the form shows the fields that category needs.');
    var s2 = section(2, 'Media details', 'Name the site and pin its exact location on the map.');
    var s3 = section(3, 'Size & price', 'Face size and the monthly rent you charge.');
    var s4 = section(4, 'Photos', 'Clear daytime photos help Brand Adda approve faster. Each image must be under 600 KB.');

    // Rows before the Media Title row are type & location; from Media Title up to the
    // size row are the details (including each category's own block); the rest is size.
    var target = s1;
    rows.forEach(function (row) {
        if (row === titleRow) target = s2;
        if (row === sizeRow) target = s3;
        target.querySelector('.vmf-body').appendChild(row);
    });

    if (photoCols.length) {
        var photoRow = document.createElement('div');
        photoRow.className = 'row';
        photoCols.forEach(function (c) {
            c.className = c.className.replace(/\bcol-md-\d+\b/, 'col-md-6');
            photoRow.appendChild(c);
        });
        s4.querySelector('.vmf-body').appendChild(photoRow);
    }

    var anchor = actions || null;
    [s1, s2, s3].concat(photoCols.length ? [s4] : []).forEach(function (s) {
        if (!s.querySelector('.vmf-body').children.length) return;
        form.insertBefore(s, anchor);
    });

    // Action bar: note on the left, Cancel / Submit on the right.
    if (actions) {
        actions.className = 'vmf-actions';
        var buttons = document.createElement('div');
        buttons.className = 'vmf-buttons';
        while (actions.firstChild) buttons.appendChild(actions.firstChild);
        var note = document.createElement('span');
        note.className = 'vmf-note';
        note.textContent = 'Fields marked * are required.';
        actions.appendChild(note);
        actions.appendChild(buttons);
    }
})();
