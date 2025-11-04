// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Module structureeditor
 *
 * @module     block_lessonmenu/structureeditor
 * @copyright  2025 David Herney @ BambuCo
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
import $ from 'jquery';
import SortableList from 'core/sortable_list';
import Log from 'core/log';
import ModalFactory from 'core/modal_factory';
import {get_strings as getStrings} from 'core/str';
import Notification from 'core/notification';

// Load strings.
var strings = [
    {key: 'changecontenttype', component: 'block_lessonmenu'},
    {key: 'invalidindentation', component: 'block_lessonmenu'},
    {key: 'invalidindentationtitle', component: 'block_lessonmenu'}
];

// Store loaded strings.
var s = [];

/**
 * Load strings from server.
 *
 * @return {Promise} Promise that is resolved when the strings are loaded.
 */
function loadStrings() {

    strings.forEach(one => {
        s[one.key] = one.key;
    });

    return new Promise((resolve) => {
        getStrings(strings).then(function(results) {
            var pos = 0;
            strings.forEach(one => {
                s[one.key] = results[pos];
                pos++;
            });

            resolve(true);
            return true;
        }).fail(function(e) {
            Log.debug('Error loading strings');
            Log.debug(e);
            return false;
        });
    });
}
// End of Load strings.

/**
 * Initialize the structure editor.
 */
export const init = async() => {
    const editorSelector = '#lessonmenu-editor';
    const $editorContainer = $(editorSelector);
    if ($editorContainer.length === 0) {
        return;
    }

    await loadStrings().catch(() => null);

    var changeTypeModal;
    var $currentItem = null;

    var config = {
        targetListSelector: null,
        moveHandlerSelector: '[data-drag-type=move]',
        isHorizontal: false,
        autoScroll: true
    };
    const list = new SortableList(editorSelector, config);

    list.getElementType = element => {
        return $.Deferred().resolve(element.attr('data-type'));
    };

    $('#lessonmenu-edit-structure-actions [data-action="adddefaultsections"]').on('click', event => {
        event.preventDefault();
        const selectedvalue = $('#lessonmenu-add-defaultsections select').val();
        const values = JSON.parse(selectedvalue);
        if (values && values.length > 0) {
            values.forEach(title => {
                const $newitem = newSection(title);
                if ($newitem === null) {
                    return;
                }
                $editorContainer.append($newitem);
            });
            const $inputs = $editorContainer.find('input[type="text"]');
            if ($inputs.length) {
                $inputs.last()[0].focus();
            }

        } else {
            Log.debug('No sections selected to add.');
        }
    });

    $('#lessonmenu-edit-structure-actions [data-action="addsection"]').on('click', event => {
        event.preventDefault();

        const $newitem = newSection('');
        if ($newitem === null) {
            return;
        }
        $editorContainer.append($newitem);
        $newitem.find('input[type="text"]')[0].focus();
    });

    $('#lessonmenu-edit-structure-actions [data-action="deletesection"]').on('click', event => {
        event.preventDefault();
        deletesection(event);
    });

    // Change the content type.
    $editorContainer.find('.item-icon').on('click', event => {
        event.preventDefault();
        const $iconspan = $(event.currentTarget);
        const $item = $iconspan.closest('[data-type="page"]');
        if ($item.length) {
            changeTypeModal.show();
            $currentItem = $item;
        }
    });

    // Move item to right.
    $editorContainer.find('[data-action="moveright"]').on('click', event => {
        event.preventDefault();
        const $item = $(event.currentTarget).closest('[data-type="page"]');
        if (!$item.length) {
            return;
        }
        const $prevItem = $item.prev('[data-type="page"]');
        let indentation = $item.data('indentation') || 0;
        // First item cannot be moved right.
        if ($prevItem.length === 0 || (($prevItem.data('indentation') - indentation) < 0)) {
            return;
        }
        indentation += 1;
        setIdentation($item, indentation);

        // Move to the right of the next item with less indentation.
        let $nextItem = $item.next('[data-type="page"]');
        while ($nextItem && $nextItem.length) {
            const nextIndentation = $nextItem.data('indentation') || 0;
            if (nextIndentation <= (indentation - 1)) {
                break;
            } else {
                setIdentation($nextItem, nextIndentation + 1);
                $nextItem = $nextItem.next('[data-type="page"]');
            }
        }

    });

    // Move item to left.
    $editorContainer.find('[data-action="moveleft"]').on('click', event => {
        event.preventDefault();
        const $item = $(event.currentTarget).closest('[data-type="page"]');
        if (!$item.length) {
            return;
        }
        let indentation = $item.data('indentation') || 0;

        // First item cannot be moved right.
        if (indentation == 0) {
            return;
        }

        indentation -= 1;
        setIdentation($item, indentation);

        // Move to the left of the next item with less indentation.
        let $nextItem = $item.next('[data-type="page"]');
        while ($nextItem && $nextItem.length) {
            const nextIndentation = $nextItem.data('indentation') || 0;
            if (nextIndentation <= (indentation + 1)) {
                break;
            } else {
                setIdentation($nextItem, nextIndentation - 1);
                $nextItem = $nextItem.next('[data-type="page"]');
            }
        }
    });

    // Save the structure.
    $('#lessonmenu-edit-structure-actions [data-action="save"]').on('click', event => {
        event.preventDefault();

        let menuitems = [];
        let section = {
            title: null,
            items: []
        };
        menuitems.push(section);

        let indentation = -1;
        $editorContainer.find('[data-type="section"], [data-type="page"]').each((index, element) => {
            const $element = $(element);

            if ($element.data('type') === 'section') {
                const sectiontitle = $element.find('input[type="text"]').val();
                if (!sectiontitle || sectiontitle.trim() === '') {
                    return; // Skip sections without title.
                }

                // Start a new section.
                section = {
                    title: sectiontitle,
                    items: []
                };
                menuitems.push(section);
                indentation = -1;
                return;
            } else if ($element.data('type') === 'page') {
                let item = {
                    "pageid": $element.data('pageid'),
                    "contenttype": $element.data('contenttype'),
                    "duration": $element.find('[data-action="duration"] input').val(),
                    "indentation": $element.data('indentation')
                };

                if (item.duration === '' || isNaN(item.duration) || item.duration < 0) {
                    item.duration = 0;
                } else {
                    item.duration = parseInt(item.duration, 10);
                }

                if ((item.indentation - indentation) > 1) {
                    // Invalid indentation, notify and stop.
                    Notification.alert(
                        s.invalidindentationtitle,
                        s.invalidindentation + ' (' + $element.find('.item-title').text() + ')'
                    );
                    throw new Error('Invalid indentation for item: ' + $element.find('.item-title').text());
                }
                indentation = item.indentation;

                section.items.push(item);
                return;
            }
        });

        const $structureTextarea = $('textarea[name="structure"]');
        $structureTextarea.val(JSON.stringify(menuitems));

        // Send the form.
        const $form = $('#lessonmenu-edit-structure-form');
        $form.trigger('submit');

    });

    // Apply the initial indentation.
    $editorContainer.find('[data-type="page"]').each((index, element) => {
        const $element = $(element);
        const indentation = $element.data('indentation') || 0;
        setIdentation($element, indentation);
    });

    // Create the change content type modal.
    const $iconModal = $('#lessonmenu-change-icon-modal');
    ModalFactory.create({
        type: ModalFactory.types.CANCEL,
        body: $iconModal.html(),
        title: s.changecontenttype,
    })
    .then(function(modal) {
        changeTypeModal = modal;

        modal.getRoot().find('#lessonmenu-change-icon-select > li').on('click', event => {
            event.preventDefault();
            const $selected = $(event.currentTarget);
            const code = $selected.data('value');

            if ($currentItem && code) {
                // Update the item icon.
                const $iconspan = $currentItem.find('.item-icon');
                if ($iconspan.length) {
                    $iconspan.html($selected.find('> span').html());
                    $iconspan.attr('title', $selected.find('> span').attr('title') || '');
                }
                // Update the data attribute.
                $currentItem.attr('data-contenttype', code);
                $currentItem.data('contenttype', code);
            }

            $currentItem = null;
            modal.hide();
        });

        return modal;
    })
    .fail(function() {
        Log.error('Error creating change content type modal');
    });
};

/**
 * Delete a section.
 *
 * @param {object} event
 */
const deletesection = (event) => {
    event.preventDefault();
    const $section = $(event.currentTarget).closest('[data-type="section"]');
    if ($section.length) {
        $section.remove();
    }
};

/**
 * Create a new section.
 *
 * @param {string} title
 * @returns {jQuery|null}
 */
const newSection = (title) => {
    const $tplsection = $('[data-tpl="section"]');
    if ($tplsection.length === 0) {
        Log.debug('Template for section not found.');
        return null;
    }
    let contentsection = $tplsection.html();
    contentsection = contentsection.replace(/\[title\]/g, title);
    const $newitem = $(contentsection);
    $newitem.find('[data-action="deletesection"]').on('click', event => {
        deletesection(event);
    });
    return $newitem;
};

/**
 * Set the indentation of an item.
 *
 * @param {object} $item
 * @param {number} indentation
 */
const setIdentation = ($item, indentation) => {
    $item.data('indentation', indentation);
    $item.attr('data-indentation', indentation);

    // Add +1 level of indentation (20px per level) including the initial padding.
    $item.css('padding-left', (20 * (indentation + 1)) + 'px');
};
