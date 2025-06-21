<?php
/**
 * Compile a PO file into a MO file using pure PHP.
 * This avoids external dependencies and binary files in the repo.
 *
 * @param string $in  Path to the input .po file.
 * @param string $out Path to the output .mo file.
 * @return bool True on success, false on failure.
 */
function wta_compile_po_to_mo( $in, $out ) {
    if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
        error_log( sprintf( 'WTA compiling %s to %s', $in, $out ) );
    }
    $fh = @fopen( $in, 'r' );
    if ( ! $fh ) {
        return false;
    }

    $translations = [];
    $msgid        = '';
    $msgstr       = '';
    $state        = null;
    $fuzzy        = false;

    while ( false !== ( $line = fgets( $fh ) ) ) {
        $line = trim( $line );

        if ( '' === $line ) {
            if ( 'msgstr' === $state && '' !== $msgid && ! $fuzzy ) {
                $translations[ $msgid ] = $msgstr;
            }
            $msgid  = '';
            $msgstr = '';
            $state  = null;
            $fuzzy  = false;
            continue;
        }

        if ( '#' === $line[0] ) {
            if ( strpos( $line, ', fuzzy' ) !== false ) {
                $fuzzy = true;
            }
            continue;
        }

        if ( 0 === strpos( $line, 'msgid "' ) ) {
            $state  = 'msgid';
            $msgid  = stripcslashes( substr( $line, 7, -1 ) );
            continue;
        }

        if ( 0 === strpos( $line, 'msgstr "' ) ) {
            $state  = 'msgstr';
            $msgstr = stripcslashes( substr( $line, 8, -1 ) );
            continue;
        }

        if ( '"' === $line[0] && $state ) {
            if ( 'msgid' === $state ) {
                $msgid .= stripcslashes( substr( $line, 1, -1 ) );
            } else {
                $msgstr .= stripcslashes( substr( $line, 1, -1 ) );
            }
        }
    }

    if ( 'msgstr' === $state && '' !== $msgid && ! $fuzzy ) {
        $translations[ $msgid ] = $msgstr;
    }
    fclose( $fh );

    $count = count( $translations );
    $ids   = '';
    $strs  = '';
    $offsets_orig = [];
    $offsets_tr   = [];

    foreach ( $translations as $id => $str ) {
        $offsets_orig[] = [ strlen( $id ), strlen( $ids ) ];
        $ids .= $id . "\0";
    }
    foreach ( $translations as $id => $str ) {
        $offsets_tr[] = [ strlen( $str ), strlen( $strs ) ];
        $strs .= $str . "\0";
    }

    $offset = 28;
    $orig_offset = $offset;
    $offset += $count * 8;
    $trans_offset = $offset;
    $offset += $count * 8;
    $ids_offset = $offset;
    $offset += strlen( $ids );
    $strs_offset = $offset;

    $mo  = pack( 'L4', 0x950412de, 0, $count, $orig_offset, $trans_offset );
    foreach ( $offsets_orig as $o ) {
        $mo .= pack( 'L2', $o[0], $ids_offset + $o[1] );
    }
    foreach ( $offsets_tr as $o ) {
        $mo .= pack( 'L2', $o[0], $strs_offset + $o[1] );
    }
    $mo .= $ids;
    $mo .= $strs;

    $success = false !== file_put_contents( $out, $mo );
    if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
        error_log( sprintf( 'WTA compile result for %s: %s', $out, $success ? 'ok' : 'failed' ) );
    }
    return $success;
}
