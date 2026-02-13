/**
 * WP Flyout Notes Component
 *
 * Handles note add/delete via REST API.
 *
 * @package WPFlyout
 * @version 3.0.0
 */
(function ($) {
    'use strict';

    const Notes = {

        /**
         * Initialize the Notes component
         */
        init: function () {
            var self = this;

            $(document)
                .on('click', '.wp-flyout-notes [data-action="add-note"]', function (e) {
                    self.handleAdd(e);
                })
                .on('click', '.wp-flyout-notes [data-action="delete-note"]', function (e) {
                    self.handleDelete(e);
                })
                .on('keydown', '.wp-flyout-notes textarea', function (e) {
                    if (e.key === 'Enter' && e.shiftKey) {
                        e.preventDefault();
                        $(this).closest('.note-add-form').find('[data-action="add-note"]').click();
                    }
                });
        },

        /**
         * Get flyout config from parent flyout element
         */
        getFlyoutConfig: function ($component) {
            var $flyout = $component.closest('.wp-flyout');
            return $flyout.data() || {};
        },

        /**
         * Handle add note
         */
        handleAdd: function (e) {
            e.preventDefault();

            var $button = $(e.currentTarget);
            var $component = $button.closest('.wp-flyout-notes');
            var $flyout = $component.closest('.wp-flyout');
            var $textarea = $component.find('textarea');
            var content = $textarea.val().trim();

            if (!content) {
                $textarea.focus();
                return;
            }

            var config = this.getFlyoutConfig($component);
            var objectType = $component.data('object-type');
            var objectId = $flyout.find('input[name="id"]').val();
            var addAction = $component.data('add-action') || 'add';

            $button.prop('disabled', true).text('Adding...');

            fetch(wpFlyout.restUrl + '/action', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-WP-Nonce': wpFlyout.restNonce
                },
                credentials: 'same-origin',
                body: JSON.stringify({
                    manager: config.manager,
                    flyout: config.flyout,
                    action_key: addAction,
                    item_id: objectId,
                    content: content,
                    object_type: objectType
                })
            })
                .then(function (response) {
                    return response.json().then(function (json) {
                        if (!response.ok) {
                            throw new Error(json.message || 'Failed to add note');
                        }
                        return json;
                    });
                })
                .then(function (response) {
                    if (response.success && response.note) {
                        var noteHtml = Notes.createNoteHtml(response.note);
                        var $list = $component.find('.notes-list');

                        $list.find('.no-notes').remove();
                        $list.prepend(noteHtml);
                        $textarea.val('').focus();
                    } else {
                        alert(response.message || 'Failed to add note');
                    }
                })
                .catch(function (error) {
                    alert(error.message || 'Error adding note');
                })
                .finally(function () {
                    $button.prop('disabled', false).text('Add Note');
                });
        },

        /**
         * Handle delete note
         */
        handleDelete: function (e) {
            e.preventDefault();

            if (!confirm('Delete this note?')) {
                return;
            }

            var $button = $(e.currentTarget);
            var $note = $button.closest('.note-item');
            var $component = $button.closest('.wp-flyout-notes');
            var $flyout = $component.closest('.wp-flyout');

            var noteId = $note.data('note-id');
            var config = this.getFlyoutConfig($component);
            var objectType = $component.data('object-type');
            var objectId = $flyout.find('input[name="id"]').val();
            var deleteAction = $component.data('delete-action') || 'delete';

            $button.prop('disabled', true);

            fetch(wpFlyout.restUrl + '/action', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-WP-Nonce': wpFlyout.restNonce
                },
                credentials: 'same-origin',
                body: JSON.stringify({
                    manager: config.manager,
                    flyout: config.flyout,
                    action_key: deleteAction,
                    item_id: objectId,
                    note_id: noteId,
                    object_type: objectType
                })
            })
                .then(function (response) {
                    return response.json().then(function (json) {
                        if (!response.ok) {
                            throw new Error(json.message || 'Failed to delete note');
                        }
                        return json;
                    });
                })
                .then(function (response) {
                    if (response.success) {
                        $note.slideUp(200, function () {
                            $note.remove();

                            var $list = $component.find('.notes-list');
                            if ($list.find('.note-item').length === 0) {
                                $list.html('<p class="no-notes">No notes yet.</p>');
                            }
                        });
                    } else {
                        alert(response.message || 'Failed to delete note');
                    }
                })
                .catch(function (error) {
                    alert(error.message || 'Error deleting note');
                })
                .finally(function () {
                    $button.prop('disabled', false);
                });
        },

        /**
         * Create note HTML
         */
        createNoteHtml: function (note) {
            var escapeHtml = function (text) {
                var div = document.createElement('div');
                div.textContent = text || '';
                return div.innerHTML;
            };

            var html = '<div class="note-item" data-note-id="' + note.id + '">';
            html += '<div class="note-header">';

            if (note.author) {
                html += '<span class="note-author">' + escapeHtml(note.author) + '</span>';
            }

            if (note.formatted_date) {
                html += '<span class="note-date">' + escapeHtml(note.formatted_date) + '</span>';
            }

            if (note.can_delete) {
                html += '<button type="button" class="button-link" data-action="delete-note">';
                html += '<span class="dashicons dashicons-trash"></span>';
                html += '</button>';
            }

            html += '</div>';
            html += '<div class="note-content">' + escapeHtml(note.content).replace(/\n/g, '<br>') + '</div>';
            html += '</div>';

            return html;
        }
    };

    $(function () {
        Notes.init();
    });

    window.WPFlyoutNotes = Notes;

})(jQuery);