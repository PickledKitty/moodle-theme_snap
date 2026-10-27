/**
 * This file is part of Moodle - http://moodle.org/
 *
 * Moodle is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Moodle is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Moodle.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @package
 * @author    Juan Ibarra juan.ibarra@openlms.net
 * @copyright Copyright (c) 2019 Open LMS (https://www.openlms.net)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * JS code to manage hide/show of full width messages drawer.
 */
define(['jquery', 'core/pubsub', 'core_message/message_drawer_events'],
    function($, PubSub, MessageDrawerEvents) {
        // Array to control which popovers are open.
        var openedpopovers = [];
        // Maximum size in pixels to consider a mobile screen
        var maxWidth = 560;
        // The box messages are typed in, in the message drawer and on the Messages page.
        var composerSelector = 'textarea[data-region="send-message-txt"]';

        /**
         * Size a message box to fit its text, from its three rows up. The styles cap its height, and it
         * scrolls inside past that.
         *
         * @param {HTMLTextAreaElement} textarea The message box.
         */
        var fitComposer = function(textarea) {
            textarea.style.height = 'auto';
            textarea.style.height = (textarea.scrollHeight + textarea.offsetHeight - textarea.clientHeight) + 'px';
        };

        /**
         * Grow the message box as the message is typed.
         *
         * Moodle's own auto-sizing (core/auto_rows) only grows the box a few lines late and then lets the text
         * scroll inside it, so it's taken off each box as the box gets focus, before anything is typed.
         */
        var initComposerGrowth = function() {
            document.addEventListener('focusin', function(e) {
                if (e.target.matches(composerSelector)) {
                    e.target.removeAttribute('data-auto-rows');
                    // Once an on-screen keyboard has opened, so phones don't zoom in on the box.
                    setTimeout(function() {
                        fitComposer(e.target);
                    }, 0);
                }
            }, true);
            document.addEventListener('input', function(e) {
                if (e.target.matches(composerSelector)) {
                    fitComposer(e.target);
                }
            });
            // Back to its three rows once a message is sent and the box emptied.
            PubSub.subscribe(MessageDrawerEvents.CONVERSATION_NEW_LAST_MESSAGE, function() {
                document.querySelectorAll(composerSelector).forEach(function(textarea) {
                    if (textarea.value === '') {
                        textarea.style.height = '';
                    }
                });
            });
        };

        return {
            init: function() {
                initComposerGrowth();

                // Listener for the admin block.
                if ($('.preferences-page-container').length === 0 && $('.message-app.main').length === 0 &&
                        ($('#page-message-edit').length != 0 || $('#page-message-index').length != 0)) {
                    var drawermessage = $('.drawer .message-app');
                    drawermessage.css('visibility', 'visible');
                    drawermessage.animate({width: '100%'}, 0);
                    document.addEventListener("messages-drawer:toggle", function () {
                        if ($('#page-message-edit').length || $('#page-message-index').length) {
                            if ($('.block_settings').hasClass('state-visible')) {
                                if ($(window).width() < maxWidth) {
                                    $('.drawer .message-app').hide();
                                } else {
                                    $('.drawer .message-app').animate({width: '50%'}, 0);
                                }
                            } else {
                                if ($(window).width() < maxWidth) {
                                    $('.drawer .message-app').show();
                                } else {
                                    $('.drawer .message-app').animate({width: '100%'}, 0);
                                }
                            }
                        }
                    });
                    // Listeners for popovers.
                    var popover = $('div.popover-region');
                    popover.on('popoverregion:menuopened', function () {
                        if ($('#page-message-edit').length || $('#page-message-index').length) {
                            var popovername = $(this).attr("id");
                            if (openedpopovers.indexOf(popovername) == -1) {
                                openedpopovers.push(popovername);
                            }
                            // If there are open popovers, hide message-drawer.
                            if (openedpopovers.length > 0) {
                                if ($(window).width() < maxWidth) {
                                    $('.drawer .message-app').hide();
                                } else {
                                    $('.drawer .message-app').animate({width: '50%'}, 0);
                                }
                            }
                        }
                    }).bind();
                    popover.on('popoverregion:menuclosed', function () {
                        if ($('#page-message-edit').length || $('#page-message-index').length) {
                            var popovername = $(this).attr("id");
                            var index = openedpopovers.indexOf(popovername);
                            openedpopovers.splice(index, 1);
                            // Only open drawer when there are no opened popovers.
                            if (openedpopovers.length == 0) {
                                if ($(window).width() < maxWidth) {
                                    $('.drawer .message-app').show();
                                } else {
                                    $('.drawer .message-app').animate({width: '100%'}, 0);
                                }
                            }
                        }
                    }).bind();
                }
            },
        };
    }
);
