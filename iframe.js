// This file is part of the EQUELLA Moodle Integration - https://github.com/equella/moodle-module
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

function pageY(elem) {
    return elem.offsetParent ? (elem.offsetTop + pageY(elem.offsetParent)) : elem.offsetTop;
}
var buffer = 20; //scroll bar buffer
function resizeIframe() {
    var height = document.documentElement.clientHeight;
    height -= pageY(document.getElementById('ifm'))+ buffer ;
    height = (height < 0) ? 0 : height;
    document.getElementById('ifm').style.height = height + 'px';
}
