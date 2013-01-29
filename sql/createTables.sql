----
-- This file is a part of Selkie.
--
-- Selkie is free software: you can redistribute it and/or modify
-- it under the terms of the GNU General Public License as published by
-- the Free Software Foundation, either version 3 of the License, or
-- (at your option) any later version.
--
-- Selkie is distributed in the hope that it will be useful,
-- but WITHOUT ANY WARRANTY; without even the implied warranty of
-- MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
-- GNU General Public License for more details.
--
-- You should have received a copy of the GNU General Public License
-- along with Selkie. If not, see <http://www.gnu.org/licenses/>.
--
-- @author Julien Fontanet <julien.fontanet@isonoe.net>
-- @license http://www.gnu.org/licenses/gpl-3.0-standalone.html GPLv3
--
-- @package Selkie
----

-- A roll is a set of vouchers.
CREATE TABLE "roll"
(
    -- Unique identifier.
    "id" SERIAL,

    -- Name of the user who created the roll.
    "creator" TEXT NOT NULL,

    -- Optional comment.
    "comment" TEXT NOT NULL DEFAULT '',

    -- Validity duration for this roll in seconds.
    "duration" INTEGER NOT NULL,

    -- When the roll was created.
    "creation" TIMESTAMP WITHOUT TIME ZONE NOT NULL DEFAULT NOW(),

    -- When the roll was activated or NULL if it was not.
    "activation" TIMESTAMP DEFAULT NULL,

    -- As the roll already be printed.
    "printed" BOOLEAN NOT NULL DEFAULT FALSE,

    -- Identifier in pfSense.
    "pfs_id" INTEGER NOT NULL,

    PRIMARY KEY ("id")
);

CREATE TABLE "voucher"
(
    -- Roll identifier.
    "roll_id" INTEGER NOT NULL,

    -- Unique identifier.
    "id" TEXT NOT NULL,

    PRIMARY KEY ("id"),
    FOREIGN KEY ("roll_id") REFERENCES "roll" ("id")
);
