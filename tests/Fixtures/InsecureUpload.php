<?php

// Deliberately unsafe scanner fixture; this file is never executed.
$file = $request->file('document');
$file->move('/uploads', $file->getClientOriginalName());
