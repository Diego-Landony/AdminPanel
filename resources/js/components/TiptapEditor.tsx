import { Button } from '@/components/ui/button';
import { Toggle } from '@/components/ui/toggle';
import Link from '@tiptap/extension-link';
import Underline from '@tiptap/extension-underline';
import { EditorContent, useEditor } from '@tiptap/react';
import StarterKit from '@tiptap/starter-kit';
import {
    Bold,
    Heading1,
    Heading2,
    Heading3,
    Italic,
    Link as LinkIcon,
    List,
    ListOrdered,
    Redo,
    Underline as UnderlineIcon,
    Undo,
} from 'lucide-react';
import { useCallback, useEffect } from 'react';

interface TiptapEditorProps {
    content?: object | null;
    onChange?: (json: object, html: string) => void;
    className?: string;
    placeholder?: string;
}

export function TiptapEditor({ content, onChange, className = '', placeholder = 'Escribe aquí...' }: TiptapEditorProps) {
    const editor = useEditor({
        extensions: [
            StarterKit.configure({
                heading: {
                    levels: [1, 2, 3],
                },
            }),
            Underline,
            Link.configure({
                openOnClick: false,
                HTMLAttributes: {
                    class: 'text-primary underline',
                },
            }),
        ],
        content: content || '',
        editorProps: {
            attributes: {
                class: 'prose prose-sm dark:prose-invert max-w-none focus:outline-none min-h-[300px] p-4',
            },
        },
        onUpdate: ({ editor }) => {
            if (onChange) {
                onChange(editor.getJSON(), editor.getHTML());
            }
        },
    });

    useEffect(() => {
        if (editor && content && JSON.stringify(editor.getJSON()) !== JSON.stringify(content)) {
            editor.commands.setContent(content);
        }
    }, [content, editor]);

    const setLink = useCallback(() => {
        if (!editor) return;

        const previousUrl = editor.getAttributes('link').href;
        const url = window.prompt('URL del enlace:', previousUrl);

        if (url === null) {
            return;
        }

        if (url === '') {
            editor.chain().focus().extendMarkRange('link').unsetLink().run();
            return;
        }

        editor.chain().focus().extendMarkRange('link').setLink({ href: url }).run();
    }, [editor]);

    if (!editor) {
        return (
            <div className={`border rounded-md bg-muted/50 animate-pulse ${className}`}>
                <div className="h-12 border-b" />
                <div className="min-h-[300px]" />
            </div>
        );
    }

    return (
        <div className={`border rounded-md overflow-hidden ${className}`}>
            {/* Toolbar */}
            <div className="flex flex-wrap items-center gap-1 p-2 border-b bg-muted/50">
                {/* Undo/Redo */}
                <Button
                    type="button"
                    variant="ghost"
                    size="sm"
                    onClick={() => editor.chain().focus().undo().run()}
                    disabled={!editor.can().undo()}
                >
                    <Undo className="h-4 w-4" />
                </Button>
                <Button
                    type="button"
                    variant="ghost"
                    size="sm"
                    onClick={() => editor.chain().focus().redo().run()}
                    disabled={!editor.can().redo()}
                >
                    <Redo className="h-4 w-4" />
                </Button>

                <div className="w-px h-6 bg-border mx-1" />

                {/* Headings */}
                <Toggle
                    size="sm"
                    pressed={editor.isActive('heading', { level: 1 })}
                    onPressedChange={() => editor.chain().focus().toggleHeading({ level: 1 }).run()}
                >
                    <Heading1 className="h-4 w-4" />
                </Toggle>
                <Toggle
                    size="sm"
                    pressed={editor.isActive('heading', { level: 2 })}
                    onPressedChange={() => editor.chain().focus().toggleHeading({ level: 2 }).run()}
                >
                    <Heading2 className="h-4 w-4" />
                </Toggle>
                <Toggle
                    size="sm"
                    pressed={editor.isActive('heading', { level: 3 })}
                    onPressedChange={() => editor.chain().focus().toggleHeading({ level: 3 }).run()}
                >
                    <Heading3 className="h-4 w-4" />
                </Toggle>

                <div className="w-px h-6 bg-border mx-1" />

                {/* Text Formatting */}
                <Toggle
                    size="sm"
                    pressed={editor.isActive('bold')}
                    onPressedChange={() => editor.chain().focus().toggleBold().run()}
                >
                    <Bold className="h-4 w-4" />
                </Toggle>
                <Toggle
                    size="sm"
                    pressed={editor.isActive('italic')}
                    onPressedChange={() => editor.chain().focus().toggleItalic().run()}
                >
                    <Italic className="h-4 w-4" />
                </Toggle>
                <Toggle
                    size="sm"
                    pressed={editor.isActive('underline')}
                    onPressedChange={() => editor.chain().focus().toggleUnderline().run()}
                >
                    <UnderlineIcon className="h-4 w-4" />
                </Toggle>

                <div className="w-px h-6 bg-border mx-1" />

                {/* Lists */}
                <Toggle
                    size="sm"
                    pressed={editor.isActive('bulletList')}
                    onPressedChange={() => editor.chain().focus().toggleBulletList().run()}
                >
                    <List className="h-4 w-4" />
                </Toggle>
                <Toggle
                    size="sm"
                    pressed={editor.isActive('orderedList')}
                    onPressedChange={() => editor.chain().focus().toggleOrderedList().run()}
                >
                    <ListOrdered className="h-4 w-4" />
                </Toggle>

                <div className="w-px h-6 bg-border mx-1" />

                {/* Link */}
                <Toggle size="sm" pressed={editor.isActive('link')} onPressedChange={setLink}>
                    <LinkIcon className="h-4 w-4" />
                </Toggle>
            </div>

            {/* Editor Content */}
            <EditorContent editor={editor} />
        </div>
    );
}

export default TiptapEditor;
