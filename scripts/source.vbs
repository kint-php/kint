'INSTALLATION:
'set editor link format to 'source:<filename>:<line>'
'move this file to C:\Program Files\bin\source.vbs and run source.reg
'customize directory mappings and editor command below

'*****************
'start of settings
'*****************
Dim command, protocol, change_slashes, debug
Set dirmap = CreateObject("Scripting.Dictionary")

'list of server paths (as regular expressions) 
'and matching local paths (as regexp replace strings)
'use this to replace the beginning of the path
'if none of the regular expressions match, the script will show an error

'local setup, when PHP and editor runs from the same machine
dirmap.Add "^([C-Z]:\\)", "$1"

'a typical remote Linux setup
'dirmap.Add "^/var/www/", "C:\Projects\"
'dirmap.Add "^/usr/share/php5/",  "C:\Projects\debug-source"

'command to execute; %f is the file name, %l is the line number
'dont forget to quote file names (quotes can be escaped by writing twice)
command = """C:\Program Files\Notepad++\notepad++.exe"" -n%l ""%f"""

'if true, forward slashes will be changed to backslashes in the filename
'(after the regex replacements)
change_slashes = True

'protocol name used in the source edit links
protocol = "source"

'if true, alerts the command before executing it
debug = False

'***************
'end of settings
'***************

Select Case WScript.Arguments.Count
	Case 0
		WScript.Echo "Wrong source link"
		WScript.Quit
End Select

Dim link, split, file, line, found, strArgs

link = Mid(WScript.Arguments.Item(0), Len(protocol) + 2)
split = InStrRev(link, ":")
file = Left(link, split - 1)
line = Mid(link, split + 1)

found = False
For Each serverPath In dirmap
	Set regexp = New RegExp
	regexp.Pattern = serverPath
	If regexp.Test(file) Then
		found = True
		Exit For
	End If
Next
If Not found Then
	WScript.Echo "don't know where to find " & file & " locally"
	WScript.Quit
End If
file = regexp.Replace(file, dirmap.Item(serverPath))
if change_slashes Then
	file = Replace(file, "/", "\")
End If

strArgs = command
strArgs = Replace(strArgs, "%l", line)
strArgs = Replace(strArgs, "%f", file)
If debug Then
	WScript.Echo strArgs
End If
Set oShell = CreateObject ("Wscript.Shell") 
oShell.Run strArgs, 0, false